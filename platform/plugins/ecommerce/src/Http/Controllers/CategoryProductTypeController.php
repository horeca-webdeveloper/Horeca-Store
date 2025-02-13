<?php

namespace Botble\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Ecommerce\Models\ProductTypes;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\CategorySpecification;

use App\Jobs\ProductCopyToS3Job;

// use Aws\S3\S3Client;
// use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Botble\Media\Facades\RvMedia;

class CategoryProductTypeController extends BaseController
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		// Get the search query from the request
		$search = $request->input('search');

		// Fetch filtered categories or all categories if no search query
		// $categories = ProductCategory::with(['productTypes', 'specifications'])
		$categories = ProductCategory::with(['specifications'])
		->whereDoesntHave('children')
		->when($search, function ($query, $search) {
			$query->where(function ($q) use ($search) {
				$q->where('id', $search)
				->orWhere('name', 'like', '%' . $search . '%');
			});
		})->orderBy('id', 'desc')
		->paginate(20)
		->through(function ($category) {
			return [
				'id' => $category->id,
				'name' => $category->name,
				// 'product_types' => $category->productTypes ? $category->productTypes->pluck('name')->implode(', ') : '',
				'specifications' => $category->specifications ? $category->specifications->pluck('specification_name')->implode(', ') : '',
			];
		});

		// Pass search query back to the view
		return view('plugins/ecommerce::category-product-type.index', compact('categories', 'search'));
	}

	/**
	 * Display the specified resource.
	 */
	public function edit($id)
	{
		// Fetch the category with product types and specifications
		$category = ProductCategory::with(['productTypes', 'specifications'])->findOrFail($id);

		// Fetch all available product types for the multi-select
		// $productTypes = ProductTypes::all(['id', 'name']);
		$specificationTypes = ['At a Glance', 'Comparison', 'Filters'];



		$specificationNameVals = CategorySpecification::get(['specification_name', 'specification_values', 'unit'])
		->groupBy('specification_name')
		->map(function ($items) {
			return collect($items)
			->sortByDesc(fn($item) => substr_count($item->specification_values, '|'))
			->first();
		})->toArray();
		$specificationNameVals = array_values($specificationNameVals);

		// ->mapWithKeys(fn($item) => [$item->specification_name => array_unique(explode('|', $item->specification_values))]);
		// ->mapWithKeys(fn($item) => [$item->specification_name => $item->specification_values])->toArray();

		// $specificationNames = CategorySpecification::pluck('specification_name')->uniqueStrict()->toArray();
		// $specificationNames = array_values($specificationNames);

		// Pass the data to the edit view
		// return view('plugins/ecommerce::category-product-type.edit', compact('category', 'productTypes'));
		return view('plugins/ecommerce::category-product-type.edit', compact('category', 'specificationTypes', 'specificationNameVals'));
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, $id)
	{
		$category = ProductCategory::findOrFail($id);

		// dd($request->all());

		// Update product types
		// $category->productTypes()->sync($request->input('product_types', []));

		// Update specifications
		$category->specifications()->delete();
		foreach ($request->input('specifications', []) as $specification) {
			if (!empty($specification['name'])) {
				$exists = $category->specifications()->where('specification_name', $specification['name'])->exists();
				if (!$exists) {
					$category->specifications()->create([
						'is_fixed' => isset($specification['is_fixed']) && $specification['is_fixed']==1 ? 1 : 0,
						'specification_type' => isset($specification['specification_type']) ? implode(',', $specification['specification_type']) : '',
						'specification_name' => $specification['name'],
						'unit' => $specification['unit'],
						'specification_values' => implode('|', array_unique(array_filter($specification['vals'], fn($val) => !is_null($val))))
					]);
				}
			}
		}

		// Get `search` and `page` query parameters
		$search = $request->input('search');
		$page = $request->input('page');

		// Redirect back to the index with the search and page parameters
		return redirect()->route('categoryFilter.index', ['search' => $search, 'page' => $page])
		->with('success', 'Category updated successfully.');
	}

	public function test_aws() {
		// $s3Client = new S3Client([
		// 	'region'  => env('AWS_DEFAULT_REGION'),
		// 	'version' => 'latest',
		// 	'credentials' => [
		// 		'key'    => env('AWS_ACCESS_KEY_ID'),
		// 		'secret' => env('AWS_SECRET_ACCESS_KEY'),
		// 	],
		// ]);

		// try {
		// 	$result = $s3Client->listBuckets();
		// 	dd($result); // Should return list of buckets if everything is correct
		// } catch (\Aws\Exception\AwsException $e) {
		// 	dd($e->getMessage()); // Catch any errors from AWS SDK
		// }


			// // Store a file on S3
		$put = Storage::disk('s3')->put('filename.txt', 'File content');
			// dd($put);

			// Retrieve a file from S3
		$file = Storage::disk('s3')->get('filename.txt');

			// Generate a URL for the file
		$url = Storage::disk('s3')->url('filename.txt');

		dd($file);

	}

	public function copyProductsToS3()
	{
		ProductCopyToS3Job::dispatch();
		return response()->json(['message' => 'Job dispatched successfully.']);
	}

	// public function productCopyToS3()
	// {
	// 	$s3Client = new S3Client([
	// 		'region'  => env('AWS_DEFAULT_REGION'),
	// 		'version' => 'latest',
	// 		'credentials' => [
	// 			'key'    => env('AWS_ACCESS_KEY_ID'),
	// 			'secret' => env('AWS_SECRET_ACCESS_KEY'),
	// 		],
	// 	]);

	// 	try {
	// 		$result = $s3Client->listBuckets();
	// 		Log::info("Bucket connected successfully.");
	// 	} catch (\Aws\Exception\AwsException $e) {
	// 		Log::error("Bucket Connection Error: " . $e->getMessage());
	// 		return;
	// 	}

	// 	$products = Product::query()->whereNotNull('images')->select(['id', 'images', 'image'])->get();
	// 	Log::info("Total product count: " . $products->count());

	// 	$i = 0;
	// 	foreach ($products as $product) {
	// 		$i++;
	// 		if ($i % 50 == 0) {
	// 			Log::info("$i records processed.");
	// 		}

	// 		$fetchedImages = $this->getImageURLs((array) $product->images ?? []);

	// 		if (count($fetchedImages) > 0) {
	// 			$product->update([
	// 				'images' => json_encode($fetchedImages),
	// 				'image'  => $fetchedImages[0],
	// 			]);
	// 		} else {
	// 			$product->update([
	// 				'images' => json_encode([]),
	// 				'image'  => null,
	// 			]);
	// 		}
	// 	}

	// 	dd('all set');
	// }

	// protected function getImageURLs(array $images): array
	// {
	// 	$images = array_values(array_filter(
	// 		array_map('trim', preg_split('/\s*,\s*/', implode(',', $images)))
	// 	));

	// 	foreach ($images as $key => $image) {
	// 		$cleanImage = str_replace(RvMedia::getUploadURL() . '/', '', $image);

	// 		if (Str::startsWith($cleanImage, ['http://', 'https://'])) {
	// 			$cleanImage = $this->uploadImageFromURL($cleanImage);
	// 		}

	// 		$images[$key] = $cleanImage;
	// 	}
	// 	return $images;
	// }

	// protected function uploadImageFromURL(?string $url): ?string
	// {
	// 	$s3Disk = Storage::disk('s3');

	// 	if (!filter_var($url, FILTER_VALIDATE_URL)) {
	// 		Log::error("Invalid URL provided: " . $url);
	// 		return null;
	// 	}

	// 	$imageContents = file_get_contents($url);
	// 	if ($imageContents === false || empty($imageContents)) {
	// 		Log::error("Failed to download image from URL: " . $url);
	// 		return null;
	// 	}

	// 	$fileNameWithQuery = basename(parse_url($url, PHP_URL_PATH));
	// 	$fileName = preg_replace('/\?.*/', '', $fileNameWithQuery);
	// 	$fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);
	// 	$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'webp';

	// 	if (empty($fileBaseName)) {
	// 		Log::error("Invalid file name extracted from URL: " . $url);
	// 		return null;
	// 	}

	// 	$sizes = [
	// 		'thumb' => [150, 150],
	// 		'medium' => [300, 300],
	// 		'large' => [790, 510]
	// 	];

	// 	try {
	// 		$image = imagecreatefromstring($imageContents);
	// 		if (!$image) {
	// 			Log::error("Failed to create image from URL: " . $url);
	// 			return null;
	// 		}

	// 		$originalPath = env('STORAGE_ENV') . "/products/{$fileBaseName}.webp";
	// 		ob_start();
	// 		imagewebp($image);
	// 		$originalData = ob_get_clean();
	// 		$s3Disk->put($originalPath, $originalData);
	// 		$imageUrl = $s3Disk->url($originalPath);

	// 		// $this->deleteLocalImages($fileBaseName);

	// 		foreach ($sizes as $sizeName => [$width, $height]) {
	// 			$resizedImage = $this->resizeImageGD($image, $width, $height);
	// 			if (!$resizedImage) {
	// 				continue;
	// 			}

	// 			$resizedPath = env('STORAGE_ENV') . "/products/{$fileBaseName}-{$width}x{$height}.webp";
	// 			ob_start();
	// 			imagewebp($resizedImage);
	// 			$resizedData = ob_get_clean();
	// 			$s3Disk->put($resizedPath, $resizedData);

	// 			// $this->deleteLocalImages("{$fileBaseName}-{$width}x{$height}");
	// 		}

	// 		imagedestroy($image);
	// 		return $imageUrl;
	// 	} catch (\Exception $e) {
	// 		Log::error("S3 Upload Error: " . $e->getMessage());
	// 		return null;
	// 	}
	// }

	// protected function resizeImageGD($image, $newWidth, $newHeight)
	// {
	// 	$width = imagesx($image);
	// 	$height = imagesy($image);

	// 	// Create new image canvas with exact width & height
	// 	$resizedImage = imagecreatetruecolor($newWidth, $newHeight);
	// 	imagealphablending($resizedImage, false);
	// 	imagesavealpha($resizedImage, true);
	// 	$transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
	// 	imagefill($resizedImage, 0, 0, $transparent);

	// 	// Force resize without aspect ratio (stretching)
	// 	imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

	// 	return $resizedImage;
	// }

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

