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
use Exception, Throwable;

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
use Botble\Ecommerce\Models\UnitOfMeasurement;

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
	protected $productFileFormatArray;

	public function __construct($data)
	{
		$this->header = $data['header'];
		$this->chunk = $data['chunk'];
		$this->userId = $data['userId'];
		$this->productFileFormatArray = $data['productFileFormatArray'];
	}

	public function handle()
	{
		$brandIdNames = Brand::pluck('name', 'id')->all();
		$storeIdNames = Store::pluck('name', 'id')->all();
		$this->categoryIdNames = ProductCategory::whereDoesntHave('children')->pluck('name', 'id')->all();
		$this->tagIdNames = ProductTag::pluck('name', 'id')->all();
		$this->productTypeIdNames = ProductTypes::pluck('name', 'id')->all();


		$log = TransactionLog::where('identifier', $this->batch()->id)->first();
		$descArray = json_decode($log->description, true) ?? ["Errors" => ''];
		$previousSuccessCount = $descArray["Success Count"] ?? 0;
		$previousFailedCount = $descArray["Failed Count"] ?? 0;

		$errorArray = [];
		$success = 0;
		$failed = 0;

		foreach ($this->chunk as $row) {
			$rowData = [];
			$rowError = [];
			if (count($this->header) == count($row)) {
				$rowData = array_combine($this->header, $row);
			} else {
				$rowError[] = 'The data in this row is not compatible for import.';
				$errorArray[] = [
					"Row Number" => $failed + $success + 2 + $previousSuccessCount + $previousFailedCount,
					"Error" => implode(' | ', $rowError),
					// "description" => '<br>&nbsp;&nbsp;<b>Header Data</b>: '.json_encode($this->header).'<br><br>&nbsp;&nbsp;<b>Row data</b>: '.json_encode($row).'<br><br>&nbsp;&nbsp;<b>Header count</b>: '.count($this->header).'<br><br>&nbsp;&nbsp;<b>Row count</b>: '.count($row)
				];
				$failed++;
				continue;
			}

			// $rowData = array_combine($this->header, $row);
			foreach ($this->productFileFormatArray as $headerKey => $variableName) {
				if (in_array($headerKey, $this->header)) {
					${$variableName} = trim($rowData[$headerKey]);
				}
			}

			/* Required data validation */
			if (empty($url) || empty($name) || empty($sku) || empty($brand) || empty($vendor) || empty($productTypes) || empty($category) || empty($status)) {
				$rowError[] = 'One or more required fields are missing.';
				$errorArray[] = [
					"Row Number" => $failed + $success + 2 + $previousSuccessCount + $previousFailedCount,
					"Error" => implode(' | ', $rowError),
				];
				$failed++;
				continue;
			}

			if (!empty($id)) {
				$product = Product::find($id);
				if (!$product) {
					$rowError[] = 'Product does not exist with the given ID.';
					$errorArray[] = [
						"Row Number" => $failed + $success + 2 + $previousSuccessCount + $previousFailedCount,
						"Error" => implode(' | ', $rowError),
					];
					$failed++;
					continue;
				}
			} else {
				$product = new Product();
			}

			/* Brand validation */
			if (!in_array($brand, array_values($brandIdNames))) {
				$rowError[] = "$brand brand does not exist.";
			} else {
				$brandId = array_search($brand, $brandIdNames);
			}

			/* Vendor validation */
			if (!in_array($vendor, array_values($storeIdNames))) {
				$rowError[] = "$vendor vendor does not exist.";
			} else {
				$storeId = array_search($vendor, $storeIdNames);
			}

			/* Product types validation */
			$productTypeArray = array_map('trim', explode(',', $productTypes));
			$productTypeArrayDiff = array_diff($productTypeArray, array_values($this->productTypeIdNames));

			if (!empty($productTypeArrayDiff)) {
				$missingProductTypes = implode(', ', $productTypeArrayDiff);
				$rowError[] = count($productTypeArrayDiff) > 1
				? "$missingProductTypes product types do not exist."
				: "$missingProductTypes product type does not exist.";
			}

			/* Category validation */
			// $categoryArray = array_map('trim', explode(',', $categories));
			// $categoryArrayDiff = array_diff($categoryArray, array_values($this->categoryIdNames));

			// if (!empty($categoryArrayDiff)) {
			// 	$missingCategories = implode(', ', $categoryArrayDiff);
			// 	$rowError[] = count($categoryArrayDiff) > 1
			// 	? "$missingCategories categories do not exist."
			// 	: "$missingCategories category does not exist.";
			// }

			// /* Category validation */
			// $categoryArray = array_map('trim', explode(',', $categories));
			// $validCategories = array_intersect($categoryArray, array_values($this->categoryIdNames));

			// if (empty($validCategories)) {
			// 	$rowError[] = "At least one valid lowest-level category should be present.";
			// } else {
			// 	$categories = implode(',', $validCategories);
			// }

			/* Category validation */
			$lowercaseCategory = strtolower($category);
			$lowercaseCategoryIdNames = array_change_key_case(array_flip($this->categoryIdNames), CASE_LOWER);
			if (array_key_exists($lowercaseCategory, $lowercaseCategoryIdNames)) {
				$categoryId = $lowercaseCategoryIdNames[$lowercaseCategory];
			} else {
				$rowError[] = "$category category does not exist or is not a valid lowest-level category.";
			}

			$usStatusArray = [
				1 => "published",
				2 => "draft",
				3 => "pending"
			];
			/* Status validation */
			if (!is_numeric($status) || !in_array($status, [1, 2, 3])) {
				$rowError[] = "Status should be numeric and either 1 for Published, 2 for Draft, or 3 for Pending.";
			} else {
				$status = $usStatusArray[$status];
			}

			/* Additional field validations */

			/* Stock status validation */
			$usStockStatusArray = [
				1 => "in_stock",
				2 => "out_of_stock",
				3 => "on_backorder"
			];

			if ($stockStatus) {
				if (!is_numeric($stockStatus) || !array_key_exists((int) $stockStatus, $usStockStatusArray)) {
					$rowError[] = "Stock status should be numeric and either 1 for In-Stock, 2 for Out of Stock, or 3 for Pre Order.";
				} else {
					$stockStatus = $usStockStatusArray[(int) $stockStatus];
				}
			} else {
				$stockStatus = null;
			}

			/* With storehouse management validation (Check for 0 if empty) */
			if ($withStorehouseManagement !== '' && (!is_numeric($withStorehouseManagement) || !in_array($withStorehouseManagement, [0, 1]))) {
				$rowError[] = "With storehouse management should be numeric and either 1 for Yes, or 0 for No.";
			} else {
				$withStorehouseManagement = $withStorehouseManagement !== '' ? (int) $withStorehouseManagement : 0;
			}

			/* Unit of measurement validation */
			$usUnitOfMeasurementArray = UnitOfMeasurement::pluck('name', 'id')->all();
			if ($unitOfMeasurement && (!is_numeric($unitOfMeasurement) || !array_key_exists((int) $unitOfMeasurement, $usUnitOfMeasurementArray))) {
				$rowError[] = "Unit of measurement should be numeric and either 1 for Each, 2 for Dozen, 3 for Box, or 4 for Case.";
			} else {
				$unitOfMeasurementID = $unitOfMeasurement ? $unitOfMeasurement : null;
			}

			/* Variant requires shipping validation (Check for 0 if empty) */
			if ($variantRequiresShipping !== '' && (!is_numeric($variantRequiresShipping) || !in_array($variantRequiresShipping, [0, 1]))) {
				$rowError[] = "Variant requires shipping should be numeric and either 1 for Yes, or 0 for No.";
			} else {
				$variantRequiresShipping = $variantRequiresShipping !== '' ? (int) $variantRequiresShipping : null;
			}

			/* Refund policy validation */
			$usRefundPolicyArray = [
				1 => "non-refundable",
				2 => "15 days",
				3 => "90 days"
			];
			if ($refundPolicy && (!is_numeric($refundPolicy) || !in_array($refundPolicy, [1, 2, 3]))) {
				$rowError[] = "Refund policy should be numeric and either 1 for Non-Refundable, 2 for 15 Days Refund, or 3 for 90 Days Refund.";
			} else {
				$refundPolicy = $refundPolicy ? $usRefundPolicyArray[$refundPolicy] ?? null : null;
			}

			/* Is featured validation (Check for 0 if empty) */
			if ($isFeatured !== '' && (!is_numeric($isFeatured) || !in_array($isFeatured, [0, 1]))) {
				$rowError[] = "Is featured should be numeric and either 1 for Enable, or 0 for Disable.";
			} else {
				$isFeatured = $isFeatured !== '' ? (int) $isFeatured : 0;
			}

			/* Weight option validation */
			$usWeightArray = [
				5 => "kg",
				6 => "g",
				9 => "lbs",
			];
			if ($weightOption && !in_array($weightOption, ['lbs', 'kg', 'g'])) {
				$rowError[] = "Weight option should be 'lbs', 'kg', or 'g'.";
			} else {
				$weightOption = $weightOption ? array_search($weightOption, $usWeightArray) : 9;
			}

			/* Dimension option validation */
			$usDimensionArray = [
				1 => "cm",
				3 => "inch",
				11 => "mm",
			];
			if ($dimensionOption && !in_array($dimensionOption, ['inch', 'cm', 'mm'])) {
				$rowError[] = "Dimension option should be 'inch', 'cm', or 'mm'.";
			} else {
				$dimensionOption = $dimensionOption ? array_search($dimensionOption, $usDimensionArray) : 3;
			}

			/* Shipping weight option validation */
			if ($shippingWeightOption && !in_array($shippingWeightOption, ['lbs', 'kg', 'g'])) {
				$rowError[] = "Shipping weight option should be 'lbs', 'kg', or 'g'.";
			} else {
				$shippingWeightOption = $shippingWeightOption ? $shippingWeightOption : 'lbs';
			}

			/* Shipping dimension option validation */
			if ($shippingDimensionOption && !in_array($shippingDimensionOption, ['inch', 'cm', 'mm'])) {
				$rowError[] = "Shipping dimension option should be 'inch', 'cm', or 'mm'.";
			} else {
				$shippingDimensionOption = $shippingDimensionOption ? $shippingDimensionOption : 'inch';
			}

			$frequentlyBoughtTogether = trim($rowData['Frequently Bought Together']);
			if ($frequentlyBoughtTogether) {
				$frequentlyBoughtTogether = json_encode(array_map(fn($value) => ['value' => trim($value)], explode(',', $frequentlyBoughtTogether)));
			} else {
				$frequentlyBoughtTogether = null;
			}

			$compareProducts = trim($rowData['Compare Products']);
			if ($compareProducts) {
				$compareProductsArray = array_unique(array_map(fn($value) => trim($value), explode(',', $compareProducts)));
				$compareProducts = !empty($compareProductsArray) ? json_encode($compareProductsArray) : null;
			} else {
				$compareProducts = null;
			}

			if ($price && $salePrice && $price < $salePrice) {
				$rowError[] = "The sale price must be less than the price.";
			}

			if ($rowError) {
				$errorArray[] = [
					"Row Number" => $failed + $success + 2 + $previousSuccessCount + $previousFailedCount,
					"Error" => implode(' | ', $rowError),
				];
				$failed++;
				continue;
			}

			/* Process Images */
			$fetchedImages = $this->getImageURLs((array) $images ?? []);

			/* Get Sale Type */
			$saleType = ($startDateSalePrice || $endDateSalePrice) ? 1 : 0;

			/* Set Quantity */
			if (!$withStorehouseManagement) {
				$quantity = null;
			}

			// Wrap in a transaction
			DB::beginTransaction();

			try {
				/*************/
				$product->name = $name;
				$product->description = !empty($description) ? $description : null;
				$product->content = !empty($content) ? $content : null;
				$product->warranty_information = !empty($warrantyInformation) ? $warrantyInformation : null;
				$product->sku = $sku;
				$product->status = $status;
				$product->delivery_days = !empty($deliveryDays) ? $deliveryDays : null;
				$product->is_featured = $isFeatured;
				$product->brand_id = $brandId;
				$product->images = json_encode($fetchedImages);
				$product->image = $fetchedImages[0] ?? null;
				$product->video_path = $uploadVideo;
				$product->stock_status = $stockStatus;
				$product->with_storehouse_management = $withStorehouseManagement;
				$product->unit_of_measurement_id = $unitOfMeasurementID;
				$product->quantity = !empty($quantity) ? $quantity : null;
				$product->cost_per_item = !empty($costPerItem) ? $costPerItem : null;
				$product->price = !empty($price) ? $price : null;
				$product->sale_price = !empty($salePrice) ? $salePrice : null;
				$product->start_date = !empty($startDateSalePrice) ? Carbon::parse($startDateSalePrice) : null;
				$product->end_date = !empty($endDateSalePrice) ? Carbon::parse($endDateSalePrice) : null;
				$product->sale_type = $saleType;
				$product->weight = !empty($weight) ? $weight : null;
				$product->weight_unit_id = $weightOption;
				$product->length = !empty($length) ? $length : null;
				$product->length_unit_id = $dimensionOption;
				$product->width = !empty($width) ? $width : null;
				$product->height = !empty($height) ? $height : null;
				$product->depth = !empty($depth) ? $depth : null;
				$product->shipping_weight_option = $shippingWeightOption;
				$product->shipping_weight = !empty($shippingWeight) ? $shippingWeight : null;
				$product->shipping_dimension_option = $shippingDimensionOption;
				$product->shipping_width = !empty($shippingWidth) ? $shippingWidth : null;
				$product->shipping_depth = !empty($shippingDepth) ? $shippingDepth : null;
				$product->shipping_height = !empty($shippingHeight) ? $shippingHeight : null;
				$product->shipping_length = !empty($shippingLength) ? $shippingLength : null;
				$product->frequently_bought_together = $frequentlyBoughtTogether;
				// $product->compare_type = !empty($compareType) ? $compareType : null;
				$product->compare_products = $compareProducts;
				$product->refund = $refundPolicy;
				$product->currency_id = 1;
				$product->variant_1_title = !empty($variant1Title) ? $variant1Title : null;
				$product->variant_1_value = !empty($variant1Value) ? $variant1Value : null;
				$product->variant_1_products = !empty($variant1Products) ? $variant1Products : null;
				$product->variant_2_title = !empty($variant2Title) ? $variant2Title : null;
				$product->variant_2_value = !empty($variant2Value) ? $variant2Value : null;
				$product->variant_2_products = !empty($variant2Products) ? $variant2Products : null;
				$product->variant_3_title = !empty($variant3Title) ? $variant3Title : null;
				$product->variant_3_value = !empty($variant3Value) ? $variant3Value : null;
				$product->variant_3_products = !empty($variant3Products) ? $variant3Products : null;
				$product->variant_color_title = !empty($variantColorTitle) ? $variantColorTitle : null;
				$product->variant_color_value = !empty($variantColorValue) ? $variantColorValue : null;
				$product->variant_color_products = !empty($variantColorProducts) ? $variantColorProducts : null;
				$product->barcode = !empty($barcode) ? $barcode : null;
				$product->minimum_order_quantity = !empty($minimumOrderQuantity) ? $minimumOrderQuantity : 0;
				$product->variant_requires_shipping = $variantRequiresShipping;
				$product->google_shopping_category = !empty($googleShoppingCategory) ? $googleShoppingCategory : null;
				$product->google_shopping_mpn = !empty($googleShoppingMpn) ? $googleShoppingMpn : null;
				$product->box_quantity = !empty($boxQuantity) ? $boxQuantity : null;

				$product->store_id = $storeId;
				$product->created_at = now();
				$product->updated_at = now();
				$product->created_by_id = $this->userId;
				$product->created_by_type = User::class;
				$product->save();

				$this->saveProductProductType($product, $productTypes);
				// $categoryIdArray = $this->changeCategoryNameToId($categories);
				// $this->saveProductCategory($product, $categoryIdArray);
				$this->saveProductCategory($product, $categoryId);
				$this->saveProductTag($product, $tags);
				$this->saveSeoMetaData($product, $seoTitle, $seoDescription);
				$this->saveSlugData($product, $url);
				$this->saveTranslation($product, $rowData);
				$this->saveDiscount($product, $rowData);

				DB::commit();

				$success++;
			} catch (\Exception $e) {
				DB::rollBack();

				$rowError[] = 'Error processing row: ' . $e->getMessage();
				$rowError[] = 'File: ' . $e->getFile();
				$rowError[] = 'Line: ' . $e->getLine();
				$errorArray[] = [
					"Row Number" => $failed + $success + 2 + $previousSuccessCount + $previousFailedCount,
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

	private function saveProductProductType($product, string $productTypes)
	{
		$productTypeIds = [];
		$productTypeNames = explode(',', $productTypes);

		foreach ($productTypeNames as $productTypeName) {
			$trimmedName = trim($productTypeName);
			if (empty($trimmedName)) {
				continue;
			}
			$productTypeId = array_search($trimmedName, $this->productTypeIdNames);
			if ($productTypeId !== false) {
				$productTypeIds[] = $productTypeId;
			}
		}
		$product->producttypes()->sync($productTypeIds);
	}

	// private function changeCategoryNameToId(string $categories)
	// {
	// 	$categoryIds = [];
	// 	$categoryNames = explode(',', $categories);

	// 	foreach ($categoryNames as $categoryName) {
	// 		$trimmedName = trim($categoryName);
	// 		if (empty($trimmedName)) {
	// 			continue;
	// 		}
	// 		$categoryId = array_search($trimmedName, $this->categoryIdNames);
	// 		if ($categoryId !== false) {
	// 			$categoryIds[] = $categoryId;
	// 		}
	// 	}

	// 	return $categoryIds;
	// }


	private function saveProductCategory($product, $categoryId)
	{
		/* Step 1: Fetch existing pivot data for the product */
		$existingCategories = $product->categories()->pluck('category_id')->toArray();

		if (!in_array($categoryId, $existingCategories)) {
			/* Clear existing specs if the category is different */
			$product->specifications()->delete();
		}

		/* Step 2: Prepare the category for syncing */
		$categoryWithTimestamp = in_array($categoryId, $existingCategories)
		? [$categoryId => []]
		: [$categoryId => ['created_at' => now()]];

		/* Step 3: Sync the single category */
		$product->categories()->sync($categoryWithTimestamp);
	}

	// private function saveProductCategory($product, $selectedCategories)
	// {
	// 	/* Step 1: Fetch existing pivot data for the product */
	// 	$existingCategories = $product->categories()->pluck('category_id')->toArray();

	// 	if (array_diff($selectedCategories, $existingCategories)) {
	// 		/* Clear existing specs */
	// 		$product->specifications()->delete();
	// 	}

	// 	/* Step 2: Prepare categories for syncing */
	// 	$categoriesWithTimestamps = collect($selectedCategories)->mapWithKeys(function ($categoryId) use ($existingCategories) {
	// 		if (in_array($categoryId, $existingCategories)) {
	// 			/* Existing category, do not modify created_at */
	// 			return [$categoryId => []];
	// 		} else {
	// 			/* New category, set created_at */
	// 			return [$categoryId => ['created_at' => now()]];
	// 		}
	// 	})->toArray();

	// 	/* Step 3: Sync categories */
	// 	$product->categories()->sync($categoriesWithTimestamps);
	// }

	private function saveProductTag($product, string $tags)
	{
		$tagIds = [];
		$tagNames = explode(',', $tags);

		foreach ($tagNames as $tagName) {
			$trimmedName = trim($tagName);
			if (empty($trimmedName)) {
				continue;
			}
			$tagId = array_search($trimmedName, $this->tagIdNames);
			if ($tagId !== false) {
				$tagIds[] = $tagId;
			} else {
				$tag = ProductTag::create(['name' => $trimmedName]);
				$tagIds[] = $tag->id;

				// Update the tagIdNames array with the new tag
				$this->tagIdNames[$tag->id] = $trimmedName;
			}
		}
		$product->tags()->sync($tagIds);
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

		$updatedMetaValue = [];
		if (!empty($seoTitle)) {
			$updatedMetaValue['seo_title'] = $seoTitle;
		}

		if (!empty($seoDescription)) {
			$updatedMetaValue['seo_description'] = $seoDescription;
		}
		$updatedMetaValue['index'] = $existingMetaValue['index'] ?? 'index';

		/* Store the updated meta value as an array */
		$seoMetaData->meta_value = [$updatedMetaValue];

		/* Save the updated meta data */
		$seoMetaData->save();
	}

	private function saveSlugData($product, $url)
	{
		if (strpos($url, '/products/') !== false) {
			$urlParts = explode('/products/', $url);
			$outputUrl = $urlParts[1];
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
		if (!empty(trim($rowData['Name (AR)'] ?? '')) || !empty(trim($rowData['Description (AR)'] ?? '')) || !empty(trim($rowData['Content (AR)'] ?? '')) || !empty(trim($rowData['Warranty Information (AR)'] ?? ''))) {
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
			'quantity1' => $rowData['Buying Quantity1'] ?? null,
			'value1' => $rowData['Discount1'] ?? null,
			'start_date1' => $rowData['Start Date1'] ?? null,
			'quantity2' => $rowData['Buying Quantity2'] ?? null,
			'value2' => $rowData['Discount2'] ?? null,
			'start_date2' => $rowData['Start Date2'] ?? null,
		];

		$requiredFieldsProvided = !empty($requiredFieldValues['quantity1']) && !empty($requiredFieldValues['value1']) && !empty($requiredFieldValues['start_date1']) && !empty($requiredFieldValues['quantity2']) && !empty($requiredFieldValues['value2']) && !empty($requiredFieldValues['start_date2']);
		if ($requiredFieldsProvided) {
			for ($i = 1; $i <= 3; $i++) {
				// Check if the current iteration is optional (3rd discount)
				$isOptional = ($i === 3);

				// Required fields for discounts
				$requiredFields = [
					'quantity' => $rowData['Buying Quantity' . $i] ?? null,
					'value' => $rowData['Discount' . $i] ?? null,
					'start_date' => $rowData['Start Date' . $i] ?? null,
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
					$discount->end_date = !empty($rowData['End Date' . $i]) ? Carbon::parse($rowData['End Date' . $i]) : null;
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
			case 'image/jpg':
			$imageCreateFunction = 'imagecreatefromjpg';
			$imageSaveFunction = 'imagejpg';
			break;
			case 'image/webp':
			$imageCreateFunction = 'imagecreatefromwebp';
			$imageSaveFunction = 'imagewebp';
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

	/**
	 * Handle a job failure.
	 */
	public function failed(Throwable $exception): void
	{
		$error = $exception->getMessage().$exception->getTraceAsString();
		logger(__("Product Import Error").': '.$error);
		// $this->jobFailed($error);
	}
}
