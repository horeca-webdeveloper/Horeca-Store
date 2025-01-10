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
class TempProductStatusController extends BaseController
{
	public function index()
	{
		$userRoleId = auth()->user()->roles->value('id');
		if ($userRoleId == 22) {
			// Fetch all temporary product changes
			$tempPricingProducts = TempProduct::where('role_id', $userRoleId)->where('created_by_id', auth()->id())->orderBy('created_at', 'desc')->get()->map(function ($product) {
				$product->discount = $product->discount ? json_decode($product->discount) : [];
				return $product;
			});
			$unitOfMeasurements = UnitOfMeasurement::pluck('name', 'id')->toArray();
			$stores = Store::pluck('name', 'id')->toArray();

			$approvalStatuses = [
				'in-process' => 'Content In Progress',
				'pending' => 'Submitted for Approval',
				'approved' => 'Ready to Publish',
				'rejected' => 'Rejected for Corrections',
			];
			return view('plugins/ecommerce::temp-products.pricing', compact('tempPricingProducts', 'unitOfMeasurements', 'stores', 'approvalStatuses'));
		} else if ($userRoleId == 19) {
			// Fetch all temporary product changes
			$tempGraphicsProducts = TempProduct::where('role_id', $userRoleId)->where('created_by_id', auth()->id())->orderBy('created_at', 'desc')->get();
			$approvalStatuses = [
				'in-process' => 'Content In Progress',
				'pending' => 'Submitted for Approval',
				'approved' => 'Ready to Publish',
				'rejected' => 'Rejected for Corrections',
			];
			return view('plugins/ecommerce::temp-products.graphics', compact('tempGraphicsProducts', 'approvalStatuses'));
		} else if ($userRoleId == 18) {
			// Fetch all temporary product changes
			$tempContentProducts = TempProduct::where('role_id', $userRoleId)->where('created_by_id', auth()->id())->orderBy('created_at', 'desc')->get();
			$approvalStatuses = [
				'in-process' => 'Content In Progress',
				'pending' => 'Submitted for Approval',
				'approved' => 'Ready to Publish',
				'rejected' => 'Rejected for Corrections',
			];
			return view('plugins/ecommerce::temp-products.content', compact('tempContentProducts', 'approvalStatuses'));
		} else {
			return back()->with('error', 'You dont have permission');
		}
	}

	public function updatePricingChanges(Request $request)
	{
		logger()->info('updatePricingChanges method called.');
		logger()->info('Request Data: ', $request->all());

		$tempProduct = TempProduct::find($request->id);
		$input = $request->all();
		if($tempProduct->approval_status=='in-process' || $tempProduct->approval_status=='rejected') {
			unset($input['_token'], $input['id'], $input['initial_approval_status'], $input['approval_status']);
			$input['discount'] = json_encode($input['discount']);
			$input['approval_status'] = isset($request->in_process) && $request->in_process==1 ? 'in-process' : 'pending';

			$tempProduct->update($input);
		}

		return redirect()->route('ecommerce/temp-products-status.index')->with('success', 'Product changes approved and updated successfully.');
	}

	public function updateGraphicsChanges(Request $request)
	{
		logger()->info('updateGraphicsChanges method called.');
		logger()->info('Request Data: ', $request->all());

		$tempProduct = TempProduct::find($request->id);
		$input = $request->all();
		if($tempProduct->approval_status=='in-process' || $tempProduct->approval_status=='rejected') {
			$approvalStatus = isset($request->in_process) && $request->in_process==1 ? 'in-process' : 'pending';

			$tempProduct->update(['approval_status' => $approvalStatus]);
		}

		return redirect()->route('ecommerce/temp-products-status.index')->with('success', 'Product changes approved and updated successfully.');
	}

	public function updateContentChanges(Request $request)
	{
		logger()->info('updateContentChanges method called.');
		logger()->info('Request Data: ', $request->all());

		$tempProduct = TempProduct::find($request->id);
		$input = $request->all();
		if($tempProduct->approval_status=='in-process' || $tempProduct->approval_status=='rejected') {
			$input['approval_status'] = isset($request->in_process) && $request->in_process==1 ? 'in-process' : 'pending';
			unset($input['_token'], $input['id'], $input['remarks'], $input['in_process']);

			$tempProduct->update($input);
		}

		return redirect()->route('ecommerce/temp-products-status.index')->with('success', 'Product changes approved and updated successfully.');
	}
}