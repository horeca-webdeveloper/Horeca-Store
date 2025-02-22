<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;

use Botble\Ecommerce\Models\Product;
use App\Models\TransactionLog;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Botble\Media\Facades\RvMedia;

class ProductCopyToS3Job implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;
	public $timeout = 43200;

	protected $offset;
	protected $limit;

	public function __construct($offset, $limit)
	{
		$this->offset = $offset;
		$this->limit = $limit;
	}

	public function handle()
	{
		$success = 0;
		$failed = 0;
		$errorArray = [];

		$products = Product::query()
		->whereNotNull('images')
		->where('images', 'like', '["http%')
		->where('images', 'not like', '["https:\\\\/\\\\/horecastore-s3-storage%')
		->select(['id', 'images', 'image'])
		->offset($this->offset)
		->limit($this->limit)
		->get();

		foreach ($products as $product) {
			$fetchedImages = $this->getImageURLs((array) $product->images ?? []);

			if (count($fetchedImages) > 0) {
				$product->update([
					'images' => json_encode($fetchedImages),
					'image' => $fetchedImages[0],
				]);
				$success++;
			} else {
				$convertErr[] = "Failed to process images.";
				$errorArray[] = [
					"Product ID" =>  $product,
					"Error" => implode(' | ', $convertErr),
				];
				$failed++;
			}

			// Update logs every 50 processed records
			if (($success + $failed) % 50 == 0) {
				$this->updateTransactionLog($success, $failed, $errorArray);
				$errorArray = [];
			}
		}

		// Final log update
		$this->updateTransactionLog($success, $failed, $errorArray);
	}

	protected function updateTransactionLog($success, $failed, $errorArray)
	{
		$log = TransactionLog::where('identifier', $this->batch()->id)->first();

		if ($log) {
			$desc = json_decode($log->description, true);
			$desc["Success Count"] += $success;
			$desc["Failed Count"] += $failed;
			$desc["Errors"] = array_merge($desc["Errors"], $errorArray);

			$log->update(['description' => json_encode($desc, JSON_UNESCAPED_UNICODE)]);
		}
	}

	protected function getImageURLs(array $images): array
	{
		$images = array_values(array_filter(
			array_map('trim', preg_split('/\s*,\s*/', implode(',', $images)))
		));

		foreach ($images as $key => $image) {
			$cleanImage = str_replace(RvMedia::getUploadURL() . '/', '', $image);

			if (Str::startsWith($cleanImage, ['http://', 'https://'])) {
				$cleanImage = $this->uploadImageFromURL($cleanImage);
			}
			if ($cleanImage) {
				$images[$key] = $cleanImage;
			}
		}
		return $images;
	}

	protected function uploadImageFromURL(?string $url): ?string
	{
		$s3Disk = Storage::disk('s3');

		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			Log::error("Invalid URL provided: " . $url);
			return null;
		}
		try {
			$imageContents = file_get_contents($url);
		} catch (\Exception $e) {
			Log::error("Failed to fetch image: " . $url." \nMessage: ". $e->getMessage());
			return null;
		}
		if ($imageContents === false || empty($imageContents)) {
			Log::error("Failed to download image from URL: " . $url);
			return null;
		}

		$fileNameWithQuery = basename(parse_url($url, PHP_URL_PATH));
		$fileName = preg_replace('/\?.*/', '', $fileNameWithQuery);
		$fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);
		$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'webp';

		if (empty($fileBaseName)) {
			Log::error("Invalid file name extracted from URL: " . $url);
			return null;
		}

		$sizes = [
			'thumb' => [150, 150],
			'medium' => [300, 300],
			'large' => [790, 510]
		];

		try {
			$image = imagecreatefromstring($imageContents);
			if (!$image) {
				Log::error("Failed to create image from URL: " . $url);
				return null;
			}

			/* Ensure image is in Truecolor format */
			if (imageistruecolor($image) === false) {
				imagepalettetotruecolor($image);
			}

			$originalPath = env('STORAGE_ENV') . "/products/{$fileBaseName}.webp";
			ob_start();
			imagewebp($image);
			$originalData = ob_get_clean();
			$s3Disk->put($originalPath, $originalData);
			$imageUrl = $s3Disk->url($originalPath);
			// $this->deleteLocalImages($fileBaseName);

			foreach ($sizes as $sizeName => [$width, $height]) {
				$resizedImage = $this->resizeImageGD($image, $width, $height);
				if (!$resizedImage) {
					continue;
				}

				$resizedPath = env('STORAGE_ENV') . "/products/{$fileBaseName}-{$width}x{$height}.webp";
				ob_start();
				imagewebp($resizedImage);
				$resizedData = ob_get_clean();
				$s3Disk->put($resizedPath, $resizedData);
				// $this->deleteLocalImages("{$fileBaseName}-{$width}x{$height}");
			}

			imagedestroy($image);
			return $imageUrl;
		} catch (\Exception $e) {
			Log::error("S3 Upload Error: " . $e->getMessage());
			return null;
		}
	}

	protected function resizeImageGD($image, $newWidth, $newHeight)
	{
		$width = imagesx($image);
		$height = imagesy($image);

		$resizedImage = imagecreatetruecolor($newWidth, $newHeight);
		imagealphablending($resizedImage, false);
		imagesavealpha($resizedImage, true);
		$transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
		imagefill($resizedImage, 0, 0, $transparent);

		imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

		return $resizedImage;
	}

	// protected function deleteLocalImages(string $fileBaseName)
	// {
	// 	$publicPath = public_path("storage/products/");
	// 	$files = glob($publicPath . $fileBaseName . '*');

	// 	foreach ($files as $file) {
	// 		if (is_file($file)) {
	// 			unlink($file);
	// 			Log::info("Deleted local file: " . $file);
	// 		}
	// 	}
	// }
}
