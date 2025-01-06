<?php

namespace Botble\Ecommerce\Http\Controllers;
use Carbon\Carbon; // Make sure to import Carbon at the top
use Botble\Ecommerce\Models\TempProduct; // Make sure this is the correct model namespace
use Botble\Ecommerce\Models\Discount; // Make sure this is the correct model namespace
use Botble\Ecommerce\Models\DiscountProduct; // Make sure this is the correct model namespace
use Botble\Ecommerce\Models\UnitOfMeasurement;
use Botble\Marketplace\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Import Schema facade
class CategoryFilterController extends BaseController
{
	public function index()
	{
		// Fetch all temporary product changes
		$tempPricingProducts = TempProduct::where('role_id', 22)->get()->map(function ($product) {
			$product->discount = $product->discount ? json_decode($product->discount) : [];
			return $product;
		});

		// dd($tempPricingProducts->toArray());

		$tempContentProducts = TempProduct::where('role_id', 18)->get();
		$tempGraphicsProducts = TempProduct::where('role_id', 19)->get();

		$unitOfMeasurements = UnitOfMeasurement::pluck('name', 'id')->toArray();
		$stores = Store::pluck('name', 'id')->toArray();

		$approvalStatuses = [
			'in-process' => 'Content In Progress',
			'pending' => 'Submitted for Approval',
			'approved' => 'Ready to Publish',
			'rejected' => 'Rejected for Corrections',
		];

		return view('plugins/ecommerce::products.partials.category-product-filter', compact('tempPricingProducts', 'tempContentProducts', 'tempGraphicsProducts', 'unitOfMeasurements', 'stores', 'approvalStatuses'));
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

		return redirect()->route('category-product-filter.index')->with('success', 'Product changes approved and updated successfully.');
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

		if($request->initial_approval_status=='pending' && $request->approval_status=='approved') {
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

		if($request->initial_approval_status=='pending' && $request->approval_status=='rejected') {
			$tempProduct->update([
				'approval_status' => $request->approval_status,
				'rejection_count' => \DB::raw('rejection_count + 1'),
				'approved_by' => auth()->id(),
				'remarks' => $request->remarks
			]);
		}

		if($request->initial_approval_status=='pending' && ($request->approval_status=='pending' || $request->approval_status=='in-process')) {
		}

		return redirect()->route('category-product-filter.index')->with('success', 'Product changes approved and updated successfully.');
	}

	public function approveContentChanges(Request $request)
	{
		// dd($request->all());
		logger()->info('approveContentChanges method called.');
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
			unset($input['_token'], $input['id'], $input['initial_approval_status'], $input['approval_status']);
			$tempProduct->product->update([
				'description' => $tempProduct->description,
				'content' => $tempProduct->content,
			]);
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
		}

		return redirect()->route('category-product-filter.index')->with('success', 'Product changes approved and updated successfully.');
	}
}