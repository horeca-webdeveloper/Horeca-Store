<?php

namespace Botble\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;

use Botble\Ecommerce\Models\TempProduct;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\UnitOfMeasurement;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Models\DiscountProduct;
use Botble\Ecommerce\Models\TempProductComment;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\ProductTypes;
use Botble\Marketplace\Models\Store;

use DB, Carbon\Carbon;

class ProductApprovalController extends BaseController
{
	public function index()
	{
		$user = auth()->user();
		$userRoles = $user->roles->pluck('name')->all() ?? [];

		$tempPricingProducts = collect();
		$tempContentProducts = collect();
		$tempGraphicsProducts = collect();

		/* Admin can see all products */
		if (in_array('admin', $userRoles) || $user->isSuperUser()) {
			$tempPricingProducts = TempProduct::where('role_id', 22)->orderBy('created_at', 'desc')->get();
			$tempContentProducts = TempProduct::where('role_id', 18)->orderBy('created_at', 'desc')->get();
			$tempGraphicsProducts = TempProduct::where('role_id', 19)->orderBy('created_at', 'desc')->get();
		}
		/* Pricing Manager can see products of Pricing User */
		else if (in_array('Pricing Manager', $userRoles)) {
			$tempPricingProducts = TempProduct::where('role_id', 22)->orderBy('created_at', 'desc')->get();
		}
		/* Content Manager can see products of Content User */
		else if (in_array('Content Manager', $userRoles)) {
			$tempContentProducts = TempProduct::where('role_id', 18)->orderBy('created_at', 'desc')->get();
		}
		/* Graphics Manager can see products of Graphics User */
		else if (in_array('Graphics Manager', $userRoles)) {
			$tempGraphicsProducts = TempProduct::where('role_id', 19)->orderBy('created_at', 'desc')->get();
		}

		$unitOfMeasurements = UnitOfMeasurement::pluck('name', 'id')->toArray();
		$stores = Store::pluck('name', 'id')->toArray();

		$approvalStatuses = [
			'in-process' => 'Content In Progress',
			'pending' => 'Submitted for Approval',
			'approved' => 'Ready to Publish',
			'rejected' => 'Rejected for Corrections',
		];
		return view('plugins/ecommerce::product-approval.index', compact(
			'tempPricingProducts',
			'tempContentProducts',
			'tempGraphicsProducts',
			'unitOfMeasurements',
			'stores',
			'approvalStatuses'
		));
	}

	public function approvePricingChanges(Request $request)
	{
		logger()->info('approvePricingChanges method called.');
		logger()->info('Request Data: ', $request->all());
		$request->validate([
			'approval_status' => 'required',
			'remarks' => [
				'required_if:approval_status,rejected'
			]
		]);
		$tempProduct = TempProduct::find($request->id);
		$input = $request->all();
		if($request->initial_approval_status=='pending' && $request->approval_status=='approved') {

			if ($request->discount) {
				// Fetch existing discount IDs related to the product
				$product = $tempProduct->product;
				$existingDiscountIds = $product->discounts->pluck('id')->toArray();

				// Keep track of processed discount IDs
				$processedDiscountIds = [];

				foreach ($request->discount as $discountDetail) {
					if (
						array_key_exists('product_quantity', $discountDetail) && $discountDetail['product_quantity']
						&& array_key_exists('discount', $discountDetail) && $discountDetail['discount']
						&& array_key_exists('discount_from_date', $discountDetail) && $discountDetail['discount_from_date']
					) {
						if (array_key_exists('discount_id', $discountDetail) && $discountDetail['discount_id']) {
							// Update existing discount
							$discountId = $discountDetail['discount_id'];
							$discount = Discount::find($discountId);

							if ($discount) {
								$discount->product_quantity = $discountDetail['product_quantity'];
								$discount->title = ($discountDetail['product_quantity']) . ' products';
								$discount->value = $discountDetail['discount'];
								$discount->start_date = Carbon::parse($discountDetail['discount_from_date']);
								$discount->end_date = array_key_exists('never_expired', $discountDetail) && $discountDetail['never_expired'] == 1
								? null
								: Carbon::parse($discountDetail['discount_to_date']);
								$discount->save();

								// Update relation
								DiscountProduct::updateOrCreate(
									['discount_id' => $discountId, 'product_id' => $product->id],
									['discount_id' => $discountId, 'product_id' => $product->id]
								);
							}

							// Mark this discount ID as processed
							$processedDiscountIds[] = $discountId;
						} else {
							// Create new discount
							$discount = new Discount();
							$discount->product_quantity = $discountDetail['product_quantity'];
							$discount->title = ($discountDetail['product_quantity']) . ' products';
							$discount->type_option = 'percentage';
							$discount->type = 'promotion';
							$discount->value = $discountDetail['discount'];
							$discount->start_date = Carbon::parse($discountDetail['discount_from_date']);
							$discount->end_date = array_key_exists('never_expired', $discountDetail) && $discountDetail['never_expired'] == 1
							? null
							: Carbon::parse($discountDetail['discount_to_date']);
							$discount->save();

							// Save relation
							$discountProduct = new DiscountProduct();
							$discountProduct->discount_id = $discount->id;
							$discountProduct->product_id = $product->id;
							$discountProduct->save();

							// Mark this discount ID as processed
							$processedDiscountIds[] = $discount->id;
						}
					}
				}

				// Delete removed discounts
				$discountsToDelete = array_diff($existingDiscountIds, $processedDiscountIds);
				if (!empty($discountsToDelete)) {
					Discount::whereIn('id', $discountsToDelete)->delete();
					DiscountProduct::whereIn('discount_id', $discountsToDelete)->delete();
				}
			}
			unset($input['_token'], $input['id'], $input['initial_approval_status'], $input['approval_status'], $input['margin'], $input['discount']);
			$tempProduct->product->update($input);
			$tempProduct->update([
				'approval_status' => $request->approval_status,
				'approved_by' => auth()->id()
			]);
		}

		if($request->initial_approval_status=='pending' && $request->approval_status=='rejected') {
			$tempProduct->update([
				'approval_status' => $request->approval_status,
				'rejection_count' => \DB::raw('rejection_count + 1'),
				'approved_by' => auth()->id(),
				'remarks' => $request->remarks
			]);
		}

		if($request->initial_approval_status=='pending' && ($request->approval_status=='pending' || $request->approval_status=='in-process')) {
			unset($input['_token'], $input['id'], $input['initial_approval_status']);
			$input['discount'] = json_encode($input['discount']);
			// dd($input);
			$tempProduct->update($input);
		}

		return redirect()->route('product_approval.index')->with('success', 'Product changes approved and updated successfully.');
	}

	public function approveGraphicsChanges(Request $request)
	{
		logger()->info('approveGraphicsChanges method called.');
		logger()->info('Request Data: ', $request->all());
		$request->validate([
			'approval_status' => 'required',
			'remarks' => [
				'required_if:approval_status,rejected'
			]
		]);
		$tempProduct = TempProduct::find($request->id);
		$input = $request->all();

		if($tempProduct->approval_status=='pending' && $request->approval_status=='approved') {
			unset($input['_token'], $input['id'], $input['initial_approval_status'], $input['approval_status']);
			$tempProduct->product->update([
				'images' => $tempProduct->images,
				'documents' => $tempProduct->documents,
				'video_path' => $tempProduct->video_path,
			]);
			$tempProduct->update([
				'approval_status' => $request->approval_status,
				'approved_by' => auth()->id(),
				'remarks' => $request->remarks
			]);
		}

		if($tempProduct->approval_status=='pending' && $request->approval_status=='rejected') {
			$tempProduct->update([
				'approval_status' => $request->approval_status,
				'rejection_count' => \DB::raw('rejection_count + 1'),
				'approved_by' => auth()->id(),
				'remarks' => $request->remarks
			]);
		}

		return redirect()->route('product_approval.index', ['tab' => 'graphics_tab'])->with('success', 'Product changes approved and updated successfully.');
	}

	public function editContentApproval($tempContentProductID)
	{
		logger()->info('Fetch product data with temp content product id: '.$tempContentProductID);
		$tempContentProduct = TempProduct::find($tempContentProductID);

		if ($tempContentProduct) {
			// Decode JSON values once
			$categoryIds = $tempContentProduct->category_ids ? json_decode($tempContentProduct->category_ids, true) : [];
			$productTypeIds = $tempContentProduct->product_type_ids ? json_decode($tempContentProduct->product_type_ids, true) : [];

			// Fetch categories and product types if IDs exist
			$categories = !empty($categoryIds) ? ProductCategory::whereIn('id', $categoryIds)->pluck('name')->toArray() : [];
			$productTypes = !empty($productTypeIds) ? ProductTypes::whereIn('id', $productTypeIds)->pluck('name')->toArray() : [];

			// Assign concatenated names back to the model
			$tempContentProduct->categories = implode(', ', $categories);
			$tempContentProduct->productTypes = implode(', ', $productTypes);
		}


		$approvalStatuses = [
			'in-process' => 'Content In Progress',
			'pending' => 'Submitted for Approval',
			'approved' => 'Ready to Publish',
			'rejected' => 'Rejected for Corrections',
		];
		$tab = 'content_tab';

		return view('plugins/ecommerce::product-approval.content-edit', compact('tempContentProduct', 'approvalStatuses', 'tab'));
	}

	public function storeComment(Request $request, $tempContentProductID)
	{
		// dd($request->all(), $tempContentProductID);
		$request->validate([
			'comment_type' => 'required',
			'highlighted_text' => 'required',
			'comment' => 'required'
		]);

		TempProductComment::create([
			'temp_product_id' => $tempContentProductID,
			'comment_type' => $request->comment_type,
			'highlighted_text' => $request->highlighted_text,
			'comment' => $request->comment,
			'status' => 'Pending',
			'created_by' => auth()->id() ?? '',
			'updated_by' => auth()->id() ?? '',
		]);

		return response()->json(['success' => true]);
	}

	public function approveContentChanges(Request $request, $tempContentProductID)
	{
		logger()->info('approveContentChanges method called.');
		logger()->info('Request Data: ', $request->all());

		$request->validate([
			'approval_status' => 'required',
			'remarks' => [
				'required_if:approval_status,rejected'
			]
		]);

		$tempProduct = TempProduct::findOrFail($tempContentProductID);
		if ($tempProduct->approval_status == 'pending' && $request->approval_status == 'approved') {
			DB::beginTransaction();
			try {
				/* Create or update the product */
				$product = $this->createOrUpdateProduct($tempProduct);

				/* Create or update related data */
				$this->updateProductCategory($tempProduct->product, $tempProduct->category_ids);
				$this->updateProductTypes($tempProduct->product, $tempProduct->product_type_ids);
				$this->updateSeoMetaData($tempProduct, $product);
				$this->updateSlugData($tempProduct, $product);

				/* Update tempProduct approval status */
				$tempProduct->update([
					'approval_status' => $request->approval_status,
					'approved_by' => auth()->id()
				]);

				DB::commit();

				return redirect()->route('product_approval.index', ['tab' => 'content_tab'])->with('success', 'Product changes approved and updated successfully.');
			} catch (\Exception $e) {
				DB::rollBack();
				logger()->error('Error in approveContentChanges: ', ['error' => $e->getMessage()]);
				return redirect()->back()->with('error', 'An error occurred while approving product changes.');
			}
		} else if ($tempProduct->approval_status == 'pending' && $request->approval_status == 'rejected') {
			$tempProduct->update([
				'approval_status' => $request->approval_status,
				'rejection_count' => DB::raw('rejection_count + 1'),
				'approved_by' => auth()->id(),
				'remarks' => $request->remarks
			]);
		}
		return redirect()->route('product_approval.index', ['tab' => 'content_tab'])->with('success', 'Product changes approved and updated successfully.');
	}

	private function createOrUpdateProduct($tempProduct)
	{
		/* If `product_id` exists, update the existing product; otherwise, create a new product */
		$product = $tempProduct->product_id
		? Product::findOrFail($tempProduct->product_id)
		: new Product();

		$product->fill([
			'name' => $tempProduct->name,
			'sku' => $tempProduct->sku,
			'description' => $tempProduct->description,
			'content' => $tempProduct->content,
			'warranty_information' => $tempProduct->warranty_information,
			'google_shopping_category' => $tempProduct->google_shopping_category,
			'created_by_id' => $tempProduct->product_id ? $tempProduct->product->created_by_id : auth()->id(),
		]);

		$product->save();

		return $product;
	}

	private function updateProductCategory($product, $categoryIds)
	{
		/* Step 1: Fetch selected category IDs from the request */
		$selectedCategories = json_decode($categoryIds, true) ?? [];

		/* Step 2: Fetch existing pivot data for the product */
		$existingCategories = $product->categories()->pluck('category_id')->toArray();

		if (array_diff($selectedCategories, $existingCategories)) {
			/* Clear existing specs */
			$product->specifications()->delete();
		}

		/* Step 3: Prepare categories for syncing */
		$categoriesWithTimestamps = collect($selectedCategories)->mapWithKeys(function ($categoryId) use ($existingCategories) {
			if (in_array($categoryId, $existingCategories)) {
				/* Existing category, do not modify created_at */
				return [$categoryId => []];
			} else {
				/* New category, set created_at */
				return [$categoryId => ['created_at' => now()]];
			}
		})->toArray();

		/* Step 4: Sync categories */
		$product->categories()->sync($categoriesWithTimestamps);
		return true;
	}

	private function updateProductTypes($product, $productTypeIds)
	{
		$productTypeIds = json_decode($productTypeIds, true) ?? [];
		$product->producttypes()->sync($productTypeIds);
		return true;
	}

	private function updateSeoMetaData($tempProduct, $product)
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
			'seo_title' => $tempProduct->seo_title,
			'seo_description' => $tempProduct->seo_description,
			'index' => $existingMetaValue['index'] ?? 'index', // Retain existing index if not provided
		];

		/* Store the updated meta value as an array */
		$seoMetaData->meta_value = [$updatedMetaValue];

		/* Save the updated meta data */
		$seoMetaData->save();
	}

	private function updateSlugData($tempProduct, $product)
	{
		/* Retrieve or create the slug data */
		$slugData = $product->slugData ?: new Slug([
			'prefix' => 'products',
			'reference_id' => $product->id,
			'reference_type' => Product::class,
		]);

		$slugData->key = $tempProduct->slug;

		$slugData->save();
	}
}