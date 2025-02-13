<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Botble\Ecommerce\Models\Product;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Botble\Media\Facades\RvMedia;

class ProductCopyToS3Job implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
	public $timeout = 43200;

	public function handle()
	{
		$s3Client = new S3Client([
			'region'  => env('AWS_DEFAULT_REGION'),
			'version' => 'latest',
			'credentials' => [
				'key'    => env('AWS_ACCESS_KEY_ID'),
				'secret' => env('AWS_SECRET_ACCESS_KEY'),
			],
		]);

		try {
			$s3Client->listBuckets();
			Log::info("Bucket connected successfully.");
		} catch (\Aws\Exception\AwsException $e) {
			Log::error("Bucket Connection Error: " . $e->getMessage());
			return;
		}

		$products = Product::query()->whereNotNull('images')->select(['id', 'images', 'image'])->get();
		Log::info("Total product count: " . $products->count());

		$i = 0;
		foreach ($products as $product) {
			$i++;
			if ($i % 50 == 0) {
				Log::info("$i records processed.");
			}

			$fetchedImages = $this->getImageURLs((array) $product->images ?? []);

			if (count($fetchedImages) > 0) {
				$product->update([
					'images' => json_encode($fetchedImages),
					'image'  => $fetchedImages[0],
				]);
			} else {
				$product->update([
					'images' => json_encode([]),
					'image'  => null,
				]);
			}
		}

		Log::info("Product images copied to S3 successfully.");
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

			$images[$key] = $cleanImage;
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
