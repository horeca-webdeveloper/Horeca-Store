<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Botble\Ecommerce\Facades\EcommerceHelper;
use RvMedia;
use Illuminate\Support\Facades\Auth;

use Botble\Ecommerce\Models\Order;

class OrderTrackingController extends Controller
{
	// public function trackOrder(Request $request): JsonResponse
	// {
	// 	// Authenticate using Bearer token
	// 	if (!Auth::check()) {
	// 		return response()->json(['message' => __('Unauthorized')], 401);
	// 	}

	// 	if (!EcommerceHelper::isOrderTrackingEnabled()) {
	// 		return response()->json(['message' => __('Order tracking is disabled')], 403);
	// 	}

	// 	$code = $request->input('order_id');

	// 	// Query the order by order code
	// 	$query = Order::query()
	// 	->where(function ($query) use ($code) {
	// 		$query
	// 		->where('code', $code)
	// 		->orWhere('code', '#' . $code);
	// 	})
	// 	->with(['address', 'products', 'shipment']);

	// 	// Ensure we're only using the authenticated user
	// 	$userId = Auth::user()->id;
	// 	$query->where('user_id', $userId);

	// 	$order = $query->first();
	// 	dd($order);

	// 	if (!$order) {
	// 		return response()->json(['message' => __('Order not found')], 404);
	// 	}

	// 	$order->load('payment');

	// 	$shipment = $order->shipment;
	// 	$shipmentStatus = $shipment ? $shipment->status : __('No shipment information available');

	// 	$statuses = [
	// 		'not_approved',
	// 		'approved',
	// 		'pending',
	// 		'arrange_shipment',
	// 		'ready_to_be_shipped_out',
	// 		'picking',
	// 		'delay_picking',
	// 		'picked',
	// 		'not_picked',
	// 		'delivering',
	// 		'delivered',
	// 		'not_delivered',
	// 		'audited',
	// 		'canceled',
	// 	];

	// 	return response()->json([
	// 		'message' => __('Order found'),
	// 		'shipment_status' => $shipmentStatus,
	// 		'data' => $order,
	// 		'all_statuses' => $statuses,
	// 	]);
	// }

	public function trackOrder(Request $request): JsonResponse
	{
		$validator = Validator::make($request->all(), [
			'order_id' => 'required',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'message' => $validator->errors()
			], 400);
		}

		$code = $request->input('order_id');
		$order = Order::with(['address', 'products', 'shipment', 'payment'])
		->where(function ($query) use ($code) {
			$query->where('code', $code)->orWhere('code', '#' . $code);
		})
		->where('user_id', auth()->id())
		->first();

		if (!$order) {
			return response()->json([
				'success' => false,
				'message' => __('Order not found')
			], 404);
		}

		// dd($order->toArray(), $order->shipment->status->getValue());

		/* Mapping shipping status */
		$shipment_status = $order->shipment ? [
			'value' => $order->shipment->status->getValue() ?? null,
			'label' => ucfirst(str_replace('_', ' ', $order->shipment->status->getValue() ?? ''))
		] : [
			'value' => 'pending',
			'label' => 'pending'
		];

		/* Mapping order status */
		$order_status = [
			'value' => $order->status->getValue() ?? null,
			'label' => ucfirst(str_replace('_', ' ', $order->status->getValue() ?? ''))
		];

		/* Mapping shipping method */
		$shipping_method = [
			'value' => $order->shipping_method->getValue() ?? null,
			'label' => ucfirst(str_replace('_', ' ', $order->shipping_method->getValue() ?? ''))
		];
		

		/* Mapping products */
		$products = $order->products->map(function ($product) {
			return [
				'id' => $product->id,
				'order_id' => $product->order_id,
				'sku' => $product->product->sku ?? null,
				'qty' => $product->qty,
				'price' => $product->price,
				'tax_amount' => $product->tax_amount,
				'product_id' => $product->product_id,
				'product_name' => $product->product_name,
				// 'product_image' => asset('storage/products/' . $product->product_image),
				'product_image' => filter_var($product->product_image, FILTER_VALIDATE_URL) 
            ? $product->product_image 
            : RvMedia::getImageUrl($product->product_image),
				'weight' => $product->weight,
				'restock_quantity' => $product->restock_quantity,
				'created_at' => $product->created_at,
				'updated_at' => $product->updated_at,
			];
		});

		/* Mapping payment_channel */
		$paymentChannel = [
			'value' => $order->payment->payment_channel->getValue() ?? null,
			'label' => match ($order->payment->payment_channel->getValue() ?? '') {
				'cod' => 'Cash on delivery (COD)',
				'bank_transfer' => match (env('APP_SITE_ENV')) {
					'us' => 'Online (Square)',
					'uae' => 'Online (Stripe)',
					default => 'Bank Transfer',
				},
				default => null,
			},
		];

		/* Mapping payment details */
		$payment = [
			'id' => $order->payment->id ?? null,
			'currency' => $order->payment->currency ?? null,
			'user_id' => $order->payment->user_id ?? null,
			'payment_channel' => $paymentChannel,
			'description' => $order->payment->description ?? null,
			'amount' => $order->payment->amount ?? null,
			'order_id' => $order->payment->order_id ?? null,
			'status' => [
				'value' => $order->payment->status->getValue() ?? null,
				'label' => ucfirst(str_replace('_', ' ', $order->payment->status->getValue() ?? ''))
			],
			'payment_type' => $order->payment->payment_type ?? null,
			'customer_id' => $order->payment->customer_id ?? null,
			'refunded_amount' => $order->payment->refunded_amount ?? null,
			'refund_note' => $order->payment->refund_note ?? null,
			'created_at' => $order->payment->created_at ?? null,
			'updated_at' => $order->payment->updated_at ?? null,
			'customer_type' => $order->payment->customer_type ?? null,
			'metadata' => $order->payment->metadata ?? null,
		];

		// $all_statuses = [
		// 	'pending',
		// 	'not_approved',
		// 	'approved',
		// 	'ready_to_be_shipped_out',
		// 	'picking',
		// 	'delivered'
		// ];
		$all_statuses = [
			'pending' => 'Pending',
			'not_approved' => 'Not Approved',
			'approved' => 'Approved',
			'ready_to_be_shipped_out' => 'Ready to be Shipped Out',
			'picking' => 'Picking',
			'delivered' => 'Delivered',
		];
		

		return response()->json([
			'message' => 'Order found',
			'shipment_status' => $shipment_status,
			'data' => [
				'id' => $order->id,
				'code' => $order->code,
				'user_id' => $order->user_id,
				'shipping_option' => $order->shipping_option,
				'shipping_method' => $shipping_method,
				'status' => $order_status,
				'amount' => $order->amount,
				'tax_amount' => $order->tax_amount,
				'shipping_amount' => $order->shipping_amount,
				'coupon_code' => $order->coupon_code,
				'discount_amount' => $order->discount_amount,
				'sub_total' => $order->sub_total,
				'is_confirmed' => $order->is_confirmed,
				'is_finished' => $order->is_finished,
				'cancellation_reason' => $order->cancellation_reason,
				'cancellation_reason_description' => $order->cancellation_reason_description,
				'completed_at' => $order->completed_at,
				'token' => request()->bearerToken(),
				'payment_id' => $order->payment_id,
				'created_at' => $order->created_at,
				'updated_at' => $order->updated_at,
				'store_id' => $order->store_id,
				'label' => $paymentChannel['label'],
				'address' => [
					'order_id' => $order->id
				],
				'products' => $products,
				'payment' => $payment,
			],
			'all_statuses' => $all_statuses,
			'shipping_address' => $order->shippingAddress ?? [],
			'billing_address' => $order->billingAddress ?? [],
		]);
	}


	public function trackOrdercard(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'order_id' => 'sometimes|required_without:email',
        'email' => 'sometimes|required_without:order_id|email',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()
        ], 400);
    }

    $query = Order::with(['address', 'products', 'shipment', 'payment']);

    if ($request->has('order_id')) {
        $code = $request->input('order_id');
        $query->where(function ($q) use ($code) {
            $q->where('code', $code)->orWhere('code', '#' . $code);
        });
    }

    if ($request->has('email')) {
        $email = $request->input('email');
        $query->whereHas('address', function ($q) use ($email) {
            $q->where('email', $email);
        });
    }

    $order = $query->first();

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => __('Order not found')
        ], 404);
    }

    return response()->json([
        'message' => 'Order found',
        'order' => [
            'id' => $order->id,
            'code' => $order->code,
            'status' => $order->status->getValue(),
            'amount' => $order->amount,
            'products' => $order->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'price' => $product->price,
                    'image' => RvMedia::getImageUrl($product->product_image),
                ];
            }),
            'shipping_address' => $order->shippingAddress ?? [],
            'billing_address' => $order->billingAddress ?? [],
        ]
    ]);
}

}
