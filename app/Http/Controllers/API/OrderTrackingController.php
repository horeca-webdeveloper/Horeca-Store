<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Botble\Ecommerce\Facades\EcommerceHelper;

use Illuminate\Support\Facades\Auth;

use Botble\Ecommerce\Models\Order;

class OrderTrackingController extends Controller
{
	public function trackOrder(Request $request): JsonResponse
	{
		// dd('called');
		// if (!EcommerceHelper::isOrderTrackingEnabled()) {
		//     return response()->json(['message' => __('Order tracking is disabled')], 403);
		// }

		$code = $request->input('order_id');

		// Query the order by order code
		$query = Order::query()
		->where(function ($query) use ($code) {
			$query
			->where('code', $code)
			->orWhere('code', '#' . $code);
		})
		->with(['address', 'products', 'shipment']);

		// Ensure we're only using the authenticated user
		$userId = Auth::user()->id;
		$query->where('user_id', $userId);

		$order = $query->first();
		dd($order);

		if (!$order) {
			return response()->json(['message' => __('Order not found')], 404);
		}

		$order->load('payment');

		$shipment = $order->shipment;
		$shipmentStatus = $shipment ? $shipment->status : __('No shipment information available');

		$statuses = [
			'not_approved',
			'approved',
			'pending',
			'arrange_shipment',
			'ready_to_be_shipped_out',
			'picking',
			'delay_picking',
			'picked',
			'not_picked',
			'delivering',
			'delivered',
			'not_delivered',
			'audited',
			'canceled',
		];

		return response()->json([
			'message' => __('Order found'),
			'shipment_status' => $shipmentStatus,
			'data' => $order,
			'all_statuses' => $statuses,
		]);
	}
}
