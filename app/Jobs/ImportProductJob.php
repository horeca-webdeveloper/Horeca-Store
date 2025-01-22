<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;

use Illuminate\Support\Facades\Log;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Botble\Ecommerce\Models\Brand;
use Botble\ACL\Models\User;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\ProductTag;
use Botble\Ecommerce\Models\ProductTypes;
use Botble\Marketplace\Models\Store;
use Botble\Base\Models\MetaBox;
use Botble\Slug\Models\Slug;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Models\DiscountProduct;
use App\Models\TransactionLog;

use Botble\Ecommerce\Services\StoreProductTagService;

class ImportProductJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

	protected $header;
	protected $chunk;
	protected $userId;
	protected $categoryIdNames;
	protected $tagIdNames;
	protected $productTypeIdNames;

	public function __construct($data)
	{
		$this->header = $data['header'];
		$this->chunk = $data['chunk'];
		$this->userId = $data['userId'];
	}

	public function handle()
	{
		$brandIdNames = Brand::pluck('name', 'id')->all();
		$this->categoryIdNames = ProductCategory::pluck('name', 'id')->all();
		$this->tagIdNames = ProductTag::pluck('name', 'id')->all();
		$this->productTypeIdNames = ProductTypes::pluck('name', 'id')->all();
		$storeIdNames = Store::pluck('name', 'id')->all();

		$errorArray = [];
		$success = 0;
		$failed = 0;

		foreach ($this->chunk as $row) {
			$rowData = array_combine($this->header, $row);
			$rowError = [];

			// if (
			// 	empty(trim($rowData['Name'])) ||
			// 	empty(trim($rowData['Description'])) ||
			// 	empty(trim($rowData['Content'])) ||
			// 	empty(trim($rowData['Warranty Information'])) ||
			// 	empty(trim($rowData['URL'])) ||
			// 	empty(trim($rowData['SKU'])) ||
			// 	empty(trim($rowData['Categories'])) ||
			// 	empty(trim($rowData['Status'])) ||
			// 	empty(trim($rowData['Delivery Days'])) ||
			// 	empty(trim($rowData['Producttypes']))
			// ) {
			// 	$rowError[] = 'Required fields data not present';
			// 	$errorArray[] = [
			// 		"Row Number" => $failed + $success + 2,
			// 		"Error" => implode(' | ', $rowError),
			// 	];
			// 	$failed++;
			// 	continue;
			// }

			// Wrap in a transaction
			DB::beginTransaction();

			try {
				/* Fetch IDs for relationships */
				$brandId = array_search($rowData['Brand'], $brandIdNames) ?: null;
				$storeId = array_search($rowData['Vendor'], $storeIdNames) ?: null;

				/* Process Images */
				$images = $this->getImageURLs((array) $rowData['Images'] ?? []);

				/* Get Sale Type */
				$saleType = ($rowData['Start Date Sale Price'] || $rowData['End Date Sale Price']) ? 1 : 0;

				/* Set Quantity */
				if (!$rowData['With Storehouse Management']) {
					$rowData['Quantity'] = null;
				}
				/*************/
				$product = new Product();

				$product->name = $rowData['Name'];
				$product->description = $rowData['Description'];
				$product->content = $rowData['Content'];
				$product->warranty_information = $rowData['Warranty Information'];
				$product->sku = $rowData['SKU'];
				$product->status = $rowData['Status'];
				$product->delivery_days = $rowData['Delivery Days'];
				$product->is_featured = $rowData['Is Featured'] ?? 0;
				$product->brand_id = $brandId;
				$product->images = json_encode($images);
				$product->image = $images[0] ?? null;
				$product->video_path = $rowData['Upload Video'];
				$product->stock_status = $rowData['Stock Status'] ?? 'in_stock';
				$product->with_storehouse_management = $rowData['With Storehouse Management'] ?? 0;
				$product->quantity = $rowData['Quantity'] ?? null;
				$product->cost_per_item = $rowData['Cost Per Item'] ?? null;
				$product->price = $rowData['Price'] ?? null;
				$product->sale_price = $rowData['Sale Price'] ?? null;
				$product->start_date = $rowData['Start Date Sale Price'] ? Carbon::parse($rowData['Start Date Sale Price']) : null;
				$product->end_date = $rowData['End Date Sale Price'] ? Carbon::parse($rowData['End Date Sale Price']) : null;
				$product->sale_type = $saleType;
				$product->weight = $rowData['Weight'] ?? null;
				$product->length = $rowData['Length'] ?? null;
				$product->width = $rowData['Width'] ?? null;
				$product->height = $rowData['Height'] ?? null;
				$product->depth = $rowData['Depth'] ?? null;
				$product->shipping_weight_option = $rowData['Shipping Weight Option'] ?? null;
				$product->shipping_weight = $rowData['Shipping Weight'] ?? null;
				$product->shipping_dimension_option = $rowData['Shipping Dimension Option'] ?? null;
				$product->shipping_width = $rowData['Shipping Width'] ?? null;
				$product->shipping_depth = $rowData['Shipping Depth'] ?? null;
				$product->shipping_height = $rowData['Shipping Height'] ?? null;
				$product->shipping_length = $rowData['Shipping Length'] ?? null;
				$product->frequently_bought_together = $rowData['Frequently Bought Together'] ?? null;
				$product->compare_type = $rowData['Compare Type'] ?? null;
				$product->compare_products = $rowData['Compare Products'] ?? null;
				$product->refund = $rowData['Refund Policy'] ?? null;
				$product->currency_id = $rowData['Currency ID'] ?? 1;
				$product->variant_1_title = $rowData['Variant 1 Title'] ?? null;
				$product->variant_1_value = $rowData['Variant 1 Value'] ?? null;
				$product->variant_1_products = $rowData['Variant 1 Products'] ?? null;
				$product->variant_2_title = $rowData['Variant 2 Title'] ?? null;
				$product->variant_2_value = $rowData['Variant 2 Value'] ?? null;
				$product->variant_2_products = $rowData['Variant 2 Products'] ?? null;
				$product->variant_3_title = $rowData['Variant 3 Title'] ?? null;
				$product->variant_3_value = $rowData['Variant 3 Value'] ?? null;
				$product->variant_3_products = $rowData['Variant 3 Products'] ?? null;
				$product->variant_color_title = $rowData['Variant Color Title'] ?? null;
				$product->variant_color_value = $rowData['Variant Color Value'] ?? null;
				$product->variant_color_products = $rowData['Variant Color Products'] ?? null;
				$product->barcode = $rowData['Barcode (ISBN,UPC,GTIN,etc.)'] ?? null;
				$product->minimum_order_quantity = $rowData['Minimum Order Quantity'] ?? 0;
				$product->variant_requires_shipping = $rowData['Variant Requires Shipping'] ?? null;
				$product->google_shopping_category = $rowData['Google Shopping Category'] ?? null;
				$product->google_shopping_mpn = $rowData['Google Shopping Mpn'] ?? null;
				$product->box_quantity = $rowData['Box Quantity'] ?? null;
				$product->store_id = $storeId;
				$product->created_at = now();
				$product->updated_at = now();
				$product->created_by_id = $this->userId;
				$product->created_by_type = User::class;
				$product->save();

				$categoryIdArray = $this->changeCategoryNameToId($rowData['Categories']);
				$this->saveProductCategory($product, $categoryIdArray);
				$this->saveProductTag($product, $rowData['Tags']);
				$this->saveProductProductType($product, $rowData['Producttypes']);
				$this->saveSeoMetaData($product, $rowData['Seo Title'], $rowData['Seo Description']);
				$this->saveSlugData($product, $rowData['URL']);
				$this->saveTranslation($product, $rowData);
				$this->saveDiscount($product, $rowData);

				DB::commit();

				$success++;
			} catch (\Exception $e) {
				DB::rollBack();

				$rowError[] = 'Error processing row: ' . $e->getMessage();
				$errorArray[] = [
					"Row Number" => $failed + $success + 2,
					"Error" => implode(' | ', $rowError),
				];
				$failed++;
			}
		}

		/* Update Transaction Log */
		$log = TransactionLog::where('identifier', $this->batch()->id)->first();
		$descArray = json_decode($log->description, true) ?? ["Errors" => ''];
		$descArray["Success Count"] = $descArray["Success Count"] + $success;
		$descArray["Failed Count"] = $descArray["Failed Count"] + $failed;
		$descArray["Errors"] = array_merge($descArray["Errors"], $errorArray);

		TransactionLog::where('id', $log->id)->update([
			'description' => json_encode($descArray),
		]);
	}


	// public function handle()
	// {
	// 	$brandIdNames = Brand::pluck('name', 'id')->all();
	// 	$this->categoryIdNames = ProductCategory::pluck('name', 'id')->all();
	// 	$this->tagIdNames = ProductTag::pluck('name', 'id')->all();
	// 	$this->productTypeIdNames = ProductTypes::pluck('name', 'id')->all();
	// 	$storeIdNames = Store::pluck('name', 'id')->all();

	// 	$errorArray = [];
	// 	$success = 0;
	// 	$failed = 0;

	// 	foreach ($this->chunk as $row) {
	// 		$rowData = array_combine($this->header, $row); // Combine header with row data
	// 		$rowError = [];
	// 		# Check Validations
	// 		if (
	// 			empty(trim($rowData['Name'])) ||
	// 			empty(trim($rowData['Description'])) ||
	// 			empty(trim($rowData['Content'])) ||
	// 			empty(trim($rowData['Warranty Information'])) ||
	// 			empty(trim($rowData['URL'])) ||
	// 			empty(trim($rowData['SKU'])) ||
	// 			empty(trim($rowData['Categories'])) ||
	// 			empty(trim($rowData['Status'])) ||
	// 			empty(trim($rowData['Delivery Days'])) ||
	// 			empty(trim($rowData['Producttypes']))
	// 		) {
	// 			$rowError[] = 'Required fields data not present';
	// 			$errorArray[] = [
	// 				"Row Number" => $failed + $success + 2,
	// 				"Error" => implode(' | ', $rowError)
	// 			];
	// 			$failed++;
	// 			continue;
	// 		} else {
	// 			$success++;
	// 		}
	// 	}

	// 	$log = TransactionLog::where('identifier', $this->batch()->id)->first();

	// 	$descArray = json_decode($log->description, true) ?? ["Errors" => ''];
	// 	$descArray["Success Count"] = $descArray["Success Count"] + $success;
	// 	$descArray["Failed Count"] = $descArray["Failed Count"] + $failed;
	// 	$descArray["Errors"] = array_merge($descArray["Errors"], $errorArray);
	// 	TransactionLog::where('id', $log->id)->update([
	// 		'description' => json_encode($descArray)
	// 	]);
	// }

	private function changeCategoryNameToId(string $categories)
	{
		$categoryIds = [];
		$categoryNames = explode(',', $categories);

		foreach ($categoryNames as $categoryName) {
			$trimmedName = trim($categoryName);
			$categoryId = array_search($trimmedName, $this->tagIdNames);
			if ($categoryId !== false) {
				$categoryIds[] = $categoryId;
			}
		}

		return $categoryIds;
	}

	private function saveProductCategory($product, $selectedCategories)
	{
		/* Step 1: Fetch existing pivot data for the product */
		$existingCategories = $product->categories()->pluck('category_id')->toArray();

		if (array_diff($selectedCategories, $existingCategories)) {
			/* Clear existing specs */
			$product->specifications()->delete();
		}

		/* Step 2: Prepare categories for syncing */
		$categoriesWithTimestamps = collect($selectedCategories)->mapWithKeys(function ($categoryId) use ($existingCategories) {
			if (in_array($categoryId, $existingCategories)) {
				/* Existing category, do not modify created_at */
				return [$categoryId => []];
			} else {
				/* New category, set created_at */
				return [$categoryId => ['created_at' => now()]];
			}
		})->toArray();

		/* Step 3: Sync categories */
		$product->categories()->sync($categoriesWithTimestamps);
		return true;
	}

	private function saveProductTag($product, string $tags)
	{
		$tagIds = [];
		$tagNames = explode(',', $tags);

		foreach ($tagNames as $tagName) {
			$trimmedName = trim($tagName);
			$tagId = array_search($trimmedName, $this->tagIdNames);
			if ($tagId !== false) {
				$tagIds[] = $tagId;
			} else {
				$tag = ProductTag::create(['name' => $tagName]);
				$tagIds[] = $tag->id;
			}
		}
		$product->tags()->sync($tagIds);

		return $true;
	}

	private function saveProductProductType($product, string $productTypes)
	{
		$productTypeIds = [];
		$productTypeNames = explode(',', $productTypes);

		foreach ($productTypeNames as $productTypeName) {
			$trimmedName = trim($productTypeName);
			$productTypeId = array_search($trimmedName, $this->productTypeIdNames);
			if ($productTypeId !== false) {
				$productTypeIds[] = $productTypeId;
			}
		}
		$product->producttypes()->sync($productTypeIds);

		return $true;
	}

	private function saveSeoMetaData($product, $seoTitle, $seoDescription)
	{
		/* Retrieve or create the SEO metadata */
		$seoMetaData = $product->seoMetaData ?: new MetaBox([
			'meta_key' => 'seo_meta',
			'reference_id' => $product->id,
			'reference_type' => Product::class,
		]);

		/* Decode existing meta_value if present */
		$existingMetaValue = is_array($seoMetaData->meta_value)
		? $seoMetaData->meta_value
		: (json_decode($seoMetaData->meta_value, true) ?? []);

		/* Ensure $existingMetaValue is an array */
		if (!is_array($existingMetaValue)) {
			$existingMetaValue = [];
		}

		/* Merge existing index with new data */
		$updatedMetaValue = [
			'seo_title' => $seoTitle,
			'seo_description' => $seoDescription,
			'index' => $existingMetaValue['index'] ?? 'index', // Retain existing index if not provided
		];

		/* Store the updated meta value as an array */
		$seoMetaData->meta_value = [$updatedMetaValue];

		/* Save the updated meta data */
		$seoMetaData->save();
	}

	private function saveSlugData($product, $url)
	{
		if (strpos($url, '/products/') !== false) {
			$urlParts = explode('/products/', $url);
			$output = $urlParts[1];
		} else {
			$outputUrl = null; // Handle the case where "/products/" is not found
		}
		/* Retrieve or create the slug data */
		$slugData = $product->slugData ?: new Slug([
			'prefix' => 'products',
			'reference_id' => $product->id,
			'reference_type' => Product::class,
		]);

		$slugData->key = $outputUrl;

		$slugData->save();
	}

	private function saveTranslation($product, $rowData)
	{
		if ($rowData['Name (AR)'] || $rowData['Description (AR)'] || $rowData['Content (AR)'] || $rowData['Warranty Information (AR)']) {
			$checkExist = $product->translations()->where('lang_code', 'ar')->first();

			if ($checkExist) {
				$checkExist->update([
					'name' => $rowData['Name (AR)'],
					'description' => $rowData['Description (AR)'],
					'content' => $rowData['Content (AR)'],
					'warranty_information' => $rowData['Warranty Information (AR)'],
				]);
			} else {
				$product->translations()->create([
					'lang_code' => 'ar',
					'ec_products_id' => $product->id,
					'name' => $rowData['Name (AR)'],
					'description' => $rowData['Description (AR)'],
					'content' => $rowData['Content (AR)'],
					'warranty_information' => $rowData['Warranty Information (AR)'],
				]);
			}
		}
	}

	private function saveDiscount($product, $rowData)
	{
		$requiredFieldValues = [
			'quantity1' => $rowData['buying_quantity_1'] ?? null,
			'value1' => $rowData['discount_1'] ?? null,
			'start_date1' => $rowData['start_date_1'] ?? null,
			'quantity2' => $rowData['buying_quantity_2'] ?? null,
			'value2' => $rowData['discount_2'] ?? null,
			'start_date2' => $rowData['start_date_2'] ?? null,
		];

		$requiredFieldsProvided = !empty($requiredFieldValues['quantity1']) && !empty($requiredFieldValues['value1']) && !empty($requiredFieldValues['start_date1']) && !empty($requiredFieldValues['quantity2']) && !empty($requiredFieldValues['value2']) && !empty($requiredFieldValues['start_date2']);
		if ($requiredFieldsProvided) {
			for ($i = 1; $i <= 3; $i++) {
				// Check if the current iteration is optional (3rd discount)
				$isOptional = ($i === 3);

				// Required fields for discounts
				$requiredFields = [
					'quantity' => $rowData['buying_quantity_' . $i] ?? null,
					'value' => $rowData['discount_' . $i] ?? null,
					'start_date' => $rowData['start_date_' . $i] ?? null,
				];

				// Check if all required fields are non-empty
				$allFieldsProvided = !empty($requiredFields['quantity']) && !empty($requiredFields['value']) && !empty($requiredFields['start_date']);

				// Validate required fields for discounts
				if ($allFieldsProvided) {
					$discount = new Discount();
					$discount->product_quantity = $requiredFields['quantity'];
					$discount->title = $discount->product_quantity . ' products';
					$discount->type_option = 'percentage';
					$discount->type = 'promotion';
					$discount->value = $requiredFields['value'];
					$discount->start_date = !empty($requiredFields['start_date']) ? Carbon::parse($requiredFields['start_date']) : null;
					$discount->end_date = !empty($rowData['end_date_' . $i]) ? Carbon::parse($rowData['end_date_' . $i]) : null;
					$discount->save();

					// Associate the discount with the product
					$discountProduct = new DiscountProduct();
					$discountProduct->discount_id = $discount->id;
					$discountProduct->product_id = $product->id;
					$discountProduct->save();
				}
			}
		}
	}

	protected function getImageURLs(array $images): array
	{
		$images = array_values(array_filter($images));

		foreach ($images as $key => $image) {
			$images[$key] = str_replace(RvMedia::getUploadURL() . '/', '', trim($image));

			if (Str::startsWith($images[$key], ['http://', 'https://'])) {
				$images[$key] = $this->uploadImageFromURL($images[$key]);
			}
		}

		return $images;
	}

	protected function uploadImageFromURL(?string $url): ?string
	{
		// Check if URL is valid
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			Log::error('Invalid URL provided: ' . $url);
			return null;
		}

		// Directory within public directory
		$productsDirectory = 'storage/products';

		// Ensure products directory exists only if it doesn't already
		$publicProductsPath = public_path($productsDirectory);
		if (!is_dir($publicProductsPath)) {
			// Create the directory only if it doesn't exist
			mkdir($publicProductsPath, 0755, true);
		}

		// Fetch the image content from the URL
		$imageContents = file_get_contents($url); // Use without error suppression to capture errors

		if ($imageContents === false) {
			Log::error('Failed to download image from URL: ' . $url);
			return null;
		}

		// Sanitize the file name
		$fileNameWithQuery = basename(parse_url($url, PHP_URL_PATH));
		$fileName = preg_replace('/\?.*/', '', $fileNameWithQuery); // Remove query parameters
		$fileBaseName = pathinfo($fileName, PATHINFO_FILENAME); // Get base name without extension

		// Save the original image
		$filePath = $publicProductsPath . '/' . $fileName;
		if (file_put_contents($filePath, $imageContents) === false) {
			Log::error('Failed to write image to file: ' . $filePath);
			return null;
		}

		// Get the MIME type of the image
		$imageInfo = getimagesize($filePath);
		if (!$imageInfo) {
			Log::error('Failed to get image size for path: ' . $filePath);
			return null;
		}
		$mimeType = $imageInfo['mime'];
		Log::info('MIME type of the image: ' . $mimeType); // Log the MIME type

		// Define the image creation function based on MIME type
		$imageCreateFunction = null;
		$imageSaveFunction = null;

		switch ($mimeType) {
			case 'image/jpeg':
			$imageCreateFunction = 'imagecreatefromjpeg';
			$imageSaveFunction = 'imagejpeg';
			break;
			case 'image/png':
			$imageCreateFunction = 'imagecreatefrompng';
			$imageSaveFunction = 'imagepng';
			break;
			case 'image/gif':
			$imageCreateFunction = 'imagecreatefromgif';
			$imageSaveFunction = 'imagegif';
			break;
			default:
			Log::error('Unsupported image type: ' . $mimeType);
			return null;
		}

		foreach (['thumb' => [150, 150], 'medium' => [300, 300], 'large' => [790, 510]] as $key => $dimensions) {
			[$width, $height] = $dimensions;

			// Load the original image
			$src = $imageCreateFunction($filePath);
			if (!$src) {
				Log::error('Failed to load image from path: ' . $filePath);
				continue;
			}

			// Create a new true color image with the new dimensions
			$dst = imagecreatetruecolor($width, $height);
			if (!$dst) {
				Log::error('Failed to create true color image for size: ' . $key);
				continue;
			}

			// Resample the original image into the new image
			if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src))) {
				Log::error('Failed to resample image for size: ' . $key);
			}

			// Save the resized image
			$resizedImagePath = $publicProductsPath . '/' . $fileBaseName . '-' . $width . 'x' . $height . '.webp';
			if (!$imageSaveFunction($dst, $resizedImagePath)) {
				Log::error('Failed to save resized image at path: ' . $resizedImagePath);
			} else {
				Log::info('Saved resized image at path: ' . $resizedImagePath);
			}

			// Free up memory
			imagedestroy($src);
			imagedestroy($dst);
		}

		// Generate the URL for the saved image
		return url('storage/products/' . $fileName);
	}
}
