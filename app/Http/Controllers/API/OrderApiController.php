<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\OrderHistory;
use Botble\Ecommerce\Models\OrderAddress;
use Botble\Ecommerce\Models\OrderProduct;
use Botble\Payment\Models\Payment;

use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Enums\OrderHistoryActionEnum;
use Botble\Payment\Enums\PaymentStatusEnum;

class OrderApiController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'shipping_method' => 'required|string',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:ec_products,id',
            'products.*.quantity' => 'required|integer|min:1',
            // 'products.*.price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 400);
        }
        try {
            // Get the authenticated user's ID
            $userId = auth()->id(); // This will get the ID of the logged-in user

            if (!$userId) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }
            $customer = Customer::find($userId);
            $customerAddress = $customer->addresses->where('is_default', 1)->first();

            // Create the order
            $order = new Order();
            $order->user_id = $userId; // Use the authenticated user's ID
            $order->shipping_option = $request->shipping_option ?? null;
            $order->shipping_method = $request->shipping_method ?? null;
            $order->status = OrderStatusEnum::PROCESSING;
            $order->amount = $request->amount ?? null;
            $order->tax_amount = $request->tax_amount ?? null;
            $order->shipping_amount = $request->shipping_amount ?? null;
            $order->description = $request->note ?? null;
            $order->coupon_code = $request->coupon_code ?? null;
            $order->discount_amount = $request->discount_amount ?? null;
            $order->sub_total = $request->sub_amount ?? null;
            $order->is_confirmed = $request->is_confirmed ?? 0;
            $order->discount_description = $request->discount_description ?? null;
            $order->is_finished = $request->is_finished ?? 0;
            $order->token = $request->token ?? null;
            $order->created_at = now();
            $order->updated_at = now();
            $order->proof_file = $request->proof_file ?? null;
            $order->store_id = $request->store_id ?? null;
            $order->save();

            if ($order) {
                OrderHistory::query()->create([
                    'action' => OrderHistoryActionEnum::CREATE_ORDER_FROM_ADMIN_PAGE,
                    'description' => trans('plugins/ecommerce::order.create_order_from_admin_page'),
                    'order_id' => $order->id,
                ]);

                OrderHistory::query()->create([
                    'action' => OrderHistoryActionEnum::CREATE_ORDER,
                    'description' => trans(
                        'plugins/ecommerce::order.new_order',
                        ['order_id' => $order->code]
                    ),
                    'order_id' => $order->id,
                ]);

                OrderHistory::query()->create([
                    'action' => OrderHistoryActionEnum::CONFIRM_ORDER,
                    'description' => trans('plugins/ecommerce::order.order_was_verified_by'),
                    'order_id' => $order->id,
                    'user_id' => $userId,
                ]);

                if ($request->payment_channel) {
                    $payment = new Payment();
                    $payment->currency = cms_currency()->getDefaultCurrency()->title;
                    $payment->user_id = $userId;
                    $payment->charge_id = Str::upper(Str::random(10));
                    $payment->payment_channel = $request->payment_channel;
                    $payment->amount = $request->amount;
                    $payment->order_id = $order->id;
                    $payment->status = $request->payment_status;
                    $payment->payment_type = $request->payment_type;
                    $payment->customer_id = $customer_id ?? null;
                    $payment->created_at = now();
                    $payment->updated_at = now();
                    $payment->customer_type = Customer::class;
                    $payment->save();

                    $order->payment_id = $payment->id;
                    $order->save();

                    if ($request->payment_status == PaymentStatusEnum::COMPLETED) {
                        OrderHistory::query()->create([
                            'action' => OrderHistoryActionEnum::CONFIRM_PAYMENT,
                            'description' => trans('plugins/ecommerce::order.payment_was_confirmed_by', [
                                'money' => format_price($order->amount),
                            ]),
                            'order_id' => $order->id,
                            'user_id' => $userId,
                        ]);
                    }
                }

                if ($customerAddress) {
                    $orderAddress = new OrderAddress();
                    $orderAddress->name = $customerAddress->name;
                    $orderAddress->phone = $customerAddress->phone;
                    $orderAddress->email = $customerAddress->email;
                    $orderAddress->state = $customerAddress->state;
                    $orderAddress->city = $customerAddress->city;
                    $orderAddress->zip_code = $customerAddress->zip_code;
                    $orderAddress->country = $customerAddress->country;
                    $orderAddress->address = $customerAddress->address;
                    $orderAddress->order_id = $order->id;
                    $orderAddress->save();
                }

                foreach ($request->products as $product) {
                    $productDetail = Product::find($product['id']);

                    $orderProduct = new OrderProduct();
                    $orderProduct->order_id = $order->id;
                    $orderProduct->product_id = $productDetail->id;
                    $orderProduct->product_name = $productDetail->name;
                    $orderProduct->product_image = $productDetail->image;
                    $orderProduct->qty = $product['quantity'];
                    $orderProduct->weight = $productDetail->weight;
                    $orderProduct->price = $productDetail->original_price;
                    $orderProduct->tax_amount = $productDetail->tax_price ?? 0;
                    $orderProduct->product_options = $productDetail->cart_options ?? null;
                    $orderProduct->options = $productDetail->cart_options ?? null;
                    $orderProduct->product_type = $productDetail->product_type;
                    $orderProduct->save();
                }
            }

            // Return the created order as JSON, including all the necessary fields
            return response()->json([
                'success' => true,
                'message' => "Order Placed Successfully",
                'data' => [
                    'order_id' => $order->id,
                    'amount' => $order->amount,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }



// public function store(Request $request)
// {
//     // Validate the incoming request data
//     $validator = Validator::make($request->all(), [
//         'shipping_method' => 'required|string',
//         'products' => 'required|array',
//         'products.*.product_id' => 'required|exists:ec_products,id',
//         'products.*.quantity' => 'required|integer|min:1',
//         'products.*.price' => 'required|numeric|min:0',
//     ]);

//     if ($validator->fails()) {
//         return response()->json($validator->errors(), 422);
//     }

//     // Get the authenticated user's ID
//     $user_id = auth()->id(); // This will get the ID of the logged-in user

//     if (!$user_id) {
//         return response()->json(['error' => 'User not authenticated'], 401);
//     }

//     // Calculate sub_total and total
//     $sub_total = 0;
//     foreach ($request->products as $product) {
//         $sub_total += $product['price'] * $product['quantity'];
//     }

//     // Optionally, calculate shipping and taxes
//     $shipping_amount = 0; // For now, assume shipping is free
//     $tax_amount = $sub_total * 0.1; // Assuming 10% tax rate
//     $amount = $sub_total + $shipping_amount + $tax_amount;

//     // Create the order
//     $order = new Order();
//     $order->user_id = $user_id; // Use the authenticated user's ID
//     $order->shipping_method = json_encode([
//         'value' => $request->shipping_method,
//         'label' => 'Default' // You can modify this if you have dynamic shipping methods
//     ]);
//     $order->amount = $amount;
//     $order->sub_total = $sub_total;
//     $order->tax_amount = $tax_amount;
//     $order->shipping_amount = $shipping_amount;
//     $order->status = json_encode([
//         'value' => 'pending',
//         'label' => 'Pending'
//     ]);  // Setting status as pending
//     $order->code = '#100000' . rand(1, 9999);  // Generate order code
//     $order->token = Str::random(32);  // Generate a unique token for the order
//     $order->is_confirmed = 0; // Assuming the order is not confirmed yet
//     $order->is_finished = 1;  // Assuming the order is finished for now
//     $order->cancellation_reason = null; // Assuming no cancellation reason
//     $order->cancellation_reason_description = null; // Assuming no cancellation description
//     $order->completed_at = null; // Assuming the order is not completed yet
//     $order->store_id = 7; // Store ID should be set accordingly
//     $order->created_at = now();
//     $order->updated_at = now();
//     $order->save();

//     // Optionally, store products or other details here as part of the order
//     $product_details = [];
//     foreach ($request->products as $product) {
//         $product_details[] = [
//             'product_id' => $product['product_id'],
//             'quantity' => $product['quantity'],
//             'price' => $product['price'],
//         ];
//     }

//     // Optionally, store product details in `description` field
//     $order->description = json_encode($product_details);
//     $order->save();

//     // Return the created order as JSON, including all the necessary fields
//     return response()->json([
//         'id' => $order->id,
//         'code' => $order->code,
//         'user_id' => $order->user_id,
//         'shipping_option' => '3', // This can be dynamic based on the shipping method
//         'shipping_method' => json_decode($order->shipping_method),
//         'status' => json_decode($order->status),
//         'amount' => $order->amount,
//         'tax_amount' => $order->tax_amount,
//         'shipping_amount' => $order->shipping_amount,
//         'description' => $order->description,
//         'coupon_code' => null, // You can adjust this if coupon codes are being used
//         'discount_amount' => 0.00, // Assuming no discount is applied
//         'sub_total' => $order->sub_total,
//         'is_confirmed' => $order->is_confirmed,
//         'discount_description' => null, // Assuming no discount description
//         'is_finished' => $order->is_finished,
//         'cancellation_reason' => $order->cancellation_reason,
//         'cancellation_reason_description' => $order->cancellation_reason_description,
//         'completed_at' => $order->completed_at,
//         'token' => $order->token,
//         'payment_id' => 5, // You can adjust this based on the actual payment method
//         'created_at' => $order->created_at,
//         'updated_at' => $order->updated_at,
//         'proof_file' => null, // Assuming no proof file is uploaded
//         'store_id' => $order->store_id,
//         'products' => json_decode($order->description) // Return the product details
//     ], 201);  // Return the full order data as JSON
// }


// Calculate sub_total based on products
private function calculateSubTotal(array $products): float
{
    $sub_total = 0.0;
    foreach ($products as $product) {
        $productModel = Product::find($product['product_id']);
        $sub_total += $productModel->price * $product['quantity'];
    }
    return $sub_total;
}

// Calculate total amount including tax and shipping
private function calculateTotalAmount(array $products, float $sub_total): float
{
    $tax = 0.1 * $sub_total;  // Assuming a 10% tax rate
    $shipping = 0.0;  // You can modify this based on the shipping method
    return $sub_total + $tax + $shipping;
}


    // Fetch all orders for a user
    // public function index(Request $request)
    // {
    //     $orders = Order::where('user_id', $request->user()->id)->get();
    //     return response()->json($orders);
    // }
public function index(Request $request)
{
    // Retrieve the orders for the logged-in user, including related data
    $orders = Order::where('user_id', $request->user()->id)->get();

    // If no orders are found, return a message
    if ($orders->isEmpty()) {
        return response()->json(['message' => 'No orders found'], 404);
    }

    // Iterate through each order and extract product details from the 'description' field
    $orders->transform(function ($order) {
        // Check if the 'description' field is not empty or null
        if ($order->description) {
            // Decode the 'description' field (which contains JSON data)
            $productDetails = json_decode($order->description, true);

            // Ensure the decoded JSON is an array
            if (is_array($productDetails)) {
                // Initialize an array to store the product details
                $products = [];

                // Loop through each product in the 'description' field and retrieve product data
                foreach ($productDetails as $item) {
                    // Fetch the product details from the 'ec_products' table based on the 'product_id'
                    $product = Product::find($item['product_id']);

                    // If the product exists, add the necessary details
                    if ($product) {
                        $products[] = [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'price' => $item['price'],
                             'sale_price' => $product->sale_price,
                            'quantity' => $item['quantity'],
                            'description' => $product->description,  // Include other details as needed
                            'images' => $product-> images
                        ];
                    }
                }

                // Attach the product details to the order as a custom attribute
                $order->setAttribute('products', $products);
            } else {
                // If the description is not a valid array, set products to an empty array
                $order->setAttribute('products', []);
            }
        } else {
            // If description is null or empty, set products to an empty array
            $order->setAttribute('products', []);
        }

        // Return the updated order with the product details
        return $order;
    });

    // Return the orders with the product details as a JSON response
    return response()->json($orders);
}


    // Get a specific order
    public function show($id)
    {
        $order = Order::findOrFail($id);
        return response()->json($order);
    }
// Get a specific order with products
// public function show($id)
// {
//     // Get the order by ID
//     $order = Order::findOrFail($id);

//     // Decode the description field from JSON
//     $productData = json_decode($order->description, true);

//     // Initialize an array to store the product details with quantity and price
//     $productsWithDetails = [];

//     // Loop through the product data
//     foreach ($productData as $item) {
//         // Fetch the product details from the ec_products table using the product_id
//         $product = Product::find($item['product_id']);

//         if ($product) {
//             // Add the product details along with quantity and price to the array
//             $productsWithDetails[] = [
//                 'product_id' => $item['product_id'],
//                 'quantity' => $item['quantity'],
//                 'price' => $item['price'],
//                 'total_price' => $item['quantity'] * $item['price'], // Calculate total price
//                 'product_details' => $product // Add product details (name, description, etc.)
//             ];
//         }
//     }

//     // Optionally, you can attach this to the order object or return it separately
//     return response()->json([
//         'order' => $order,
//         'products' => $productsWithDetails
//     ]);
// }

    // Update order status
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();

        return response()->json($order);
    }

    // Delete an order
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(null, 204);
    }

    public function getGenerateInvoice(Order $order, Request $request)
    {
        if (! $order->isInvoiceAvailable()) {
            abort(404);
        }

        if ($request->input('type') == 'print') {
            return InvoiceHelper::streamInvoice($order->invoice);
        }

        return InvoiceHelper::downloadInvoice($order->invoice);
    }

    public function postConfirm(Request $request)
    {
        /**
         * @var Order $order
         */
        $order = Order::query()->findOrFail($request->input('order_id'));

        OrderHelper::confirmOrder($order);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::order.confirm_order_success'));
    }

    public function postResendOrderConfirmationEmail(Order $order)
    {
        $result = OrderHelper::sendOrderConfirmationEmail($order);

        if (! $result) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/ecommerce::order.error_when_sending_email'));
        }

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::order.sent_confirmation_email_success'));
    }

    public function getShipmentForm(
        Order $order,
        HandleShippingFeeService $shippingFeeService,
        Request $request
    ) {
        if ($request->has('weight')) {
            $weight = $request->input('weight');
        } else {
            $weight = $order->products_weight;
        }

        $shippingData = [
            'address' => $order->address->address,
            'country' => $order->address->country,
            'state' => $order->address->state,
            'city' => $order->address->city,
            'weight' => $weight,
            'order_total' => $order->amount,
        ];

        $shipping = $shippingFeeService->execute($shippingData);

        $storeLocators = StoreLocator::query()->where('is_shipping_location', true)->get();

        $url = route('orders.create-shipment', $order->getKey());

        if ($request->has('view')) {
            return view(
                'plugins/ecommerce::orders.shipment-form',
                compact('order', 'weight', 'shipping', 'storeLocators', 'url')
            );
        }

        return $this
            ->httpResponse()->setData(
                view(
                    'plugins/ecommerce::orders.shipment-form',
                    compact('order', 'weight', 'shipping', 'storeLocators', 'url')
                )->render()
            );
    }

    public function postCreateShipment(Order $order, CreateShipmentRequest $request)
    {
        $result = $this->httpResponse();

        $shipment = [
            'order_id' => $order->getKey(),
            'user_id' => Auth::id(),
            'weight' => $order->products_weight,
            'note' => $request->input('note'),
            'cod_amount' => $request->input('cod_amount') ?? (is_plugin_active(
                'payment'
            ) && $order->payment->status != PaymentStatusEnum::COMPLETED ? $order->amount : 0),
            'cod_status' => 'pending',
            'type' => $request->input('method'),
            'status' => ShippingStatusEnum::DELIVERING,
            'price' => $order->shipping_amount,
            'store_id' => $request->input('store_id'),
        ];

        $store = StoreLocator::query()->find($request->input('store_id'));

        if (! $store) {
            $shipment['store_id'] = StoreLocator::query()->where('is_primary', true)->value('id');
        }

        $result = $result->setMessage(trans('plugins/ecommerce::order.order_was_sent_to_shipping_team'));

        if (! $result->isError()) {
            $order->fill([
                'status' => OrderStatusEnum::PROCESSING,
                'shipping_method' => $request->input('method'),
                'shipping_option' => $request->input('option'),
            ]);
            $order->save();

            $shipment = Shipment::query()->create($shipment);

            OrderHistory::query()->create([
                'action' => OrderHistoryActionEnum::CREATE_SHIPMENT,
                'description' => $result->getMessage() . ' ' . trans('plugins/ecommerce::order.by_username'),
                'order_id' => $order->getKey(),
                'user_id' => Auth::id(),
            ]);

            ShipmentHistory::query()->create([
                'action' => 'create_from_order',
                'description' => trans('plugins/ecommerce::order.shipping_was_created_from'),
                'shipment_id' => $shipment->id,
                'order_id' => $order->getKey(),
                'user_id' => Auth::id(),
            ]);
        }

        return $result;
    }

    public function postCancelShipment(Shipment $shipment)
    {
        $shipment->update(['status' => ShippingStatusEnum::CANCELED]);

        OrderHistory::query()->create([
            'action' => OrderHistoryActionEnum::CANCEL_SHIPMENT,
            'description' => trans('plugins/ecommerce::order.shipping_was_canceled_by'),
            'order_id' => $shipment->order_id,
            'user_id' => Auth::id(),
        ]);

        return $this
            ->httpResponse()
            ->setData([
                'status' => ShippingStatusEnum::CANCELED,
                'status_text' => ShippingStatusEnum::CANCELED()->label(),
            ])
            ->setMessage(trans('plugins/ecommerce::order.shipping_was_canceled_success'));
    }

    public function postUpdateShippingAddress(OrderAddress $address, AddressRequest $request)
    {
        $address->fill($request->input());
        $address->save();

        if ($address->order->status == OrderStatusEnum::CANCELED) {
            abort(401);
        }

        return $this
            ->httpResponse()
            ->setData([
                'line' => view('plugins/ecommerce::orders.shipping-address.line', compact('address'))->render(),
                'detail' => view('plugins/ecommerce::orders.shipping-address.detail', compact('address'))->render(),
            ])
            ->setMessage(trans('plugins/ecommerce::order.update_shipping_address_success'));
    }

    public function postUpdateTaxInformation(OrderTaxInformation $taxInformation, Request $request)
    {
        $validated = $request->validate([
            'company_tax_code' => 'required|string|min:3|max:20',
            'company_name' => 'required|string|min:3|max:120',
            'company_address' => 'required|string|min:3|max:255',
            'company_email' => 'required|email|min:6|max:60',
        ]);

        $taxInformation->load(['order']);

        $taxInformation->update($validated);

        if ($taxInformation->order->status === OrderStatusEnum::CANCELED) {
            abort(401);
        }

        return $this
            ->httpResponse()
            ->setData(view('plugins/ecommerce::orders.tax-information.detail', ['tax' => $taxInformation])->render())
            ->setMessage(trans('plugins/ecommerce::order.tax_info.update_success'));
    }

    public function postCancelOrder(Order $order)
    {
        if (! $order->canBeCanceledByAdmin()) {
            abort(403);
        }

        OrderHelper::cancelOrder($order);

        OrderHistory::query()->create([
            'action' => OrderHistoryActionEnum::CANCEL_ORDER,
            'description' => trans('plugins/ecommerce::order.order_was_canceled_by'),
            'order_id' => $order->id,
            'user_id' => Auth::id(),
        ]);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::order.cancel_success'));
    }

    public function postConfirmPayment(Order $order)
    {
        if ($order->status === OrderStatusEnum::PENDING) {
            $order->status = OrderStatusEnum::PROCESSING;
        }

        $order->save();

        $order->load(['payment']);

        OrderHelper::confirmPayment($order);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::order.confirm_payment_success'));
    }

    public function postRefund(Order $order, RefundRequest $request)
    {
        if (is_plugin_active('payment') && $request->input(
            'refund_amount'
        ) > ($order->payment->amount - $order->payment->refunded_amount)) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(
                    trans('plugins/ecommerce::order.refund_amount_invalid', [
                        'price' => format_price(
                            $order->payment->amount - $order->payment->refunded_amount,
                            get_application_currency()
                        ),
                    ])
                );
        }

        foreach ($request->input('products', []) as $productId => $quantity) {
            $orderProduct = OrderProduct::query()->where([
                'product_id' => $productId,
                'order_id' => $order->getKey(),
            ])
                ->first();

            if ($quantity > ($orderProduct->qty - $orderProduct->restock_quantity)) {
                $this
                    ->httpResponse()
                    ->setError()
                    ->setMessage(trans('plugins/ecommerce::order.number_of_products_invalid'));

                break;
            }
        }

        $response = apply_filters(ACTION_BEFORE_POST_ORDER_REFUND_ECOMMERCE, $this->httpResponse(), $order, $request);

        if ($response->isError()) {
            return $response;
        }

        $payment = $order->payment;
        if (! $payment) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/ecommerce::order.cannot_found_payment_for_this_order'));
        }

        $refundAmount = $request->input('refund_amount');

        if ($paymentService = get_payment_is_support_refund_online($payment)) {
            $paymentResponse = (new $paymentService());
            if (method_exists($paymentService, 'setCurrency')) {
                $paymentResponse = $paymentResponse->setCurrency($payment->currency);
            }

            $optionRefunds = [
                'refund_note' => $request->input('refund_note'),
                'order_id' => $order->getKey(),
            ];

            $paymentResponse = $paymentResponse->refundOrder($payment->charge_id, $refundAmount, $optionRefunds);

            if (Arr::get($paymentResponse, 'error', true)) {
                return $this
                    ->httpResponse()
                    ->setError()
                    ->setMessage(Arr::get($paymentResponse, 'message', ''));
            }

            if (Arr::get($paymentResponse, 'data.refund_redirect_url')) {
                return $this
                    ->httpResponse()
                    ->setNextUrl($paymentResponse['data']['refund_redirect_url'])
                    ->setData($paymentResponse['data'])
                    ->setMessage(Arr::get($paymentResponse, 'message', ''));
            }

            $refundData = (array) Arr::get($paymentResponse, 'data', []);

            $response->setData($refundData);

            $refundData['_data_request'] = $request->except(['_token']) + [
                    'currency' => $payment->currency,
                    'created_at' => Carbon::now(),
                ];
            $metadata = $payment->metadata;
            $refunds = Arr::get($metadata, 'refunds', []);
            $refunds[] = $refundData;
            Arr::set($metadata, 'refunds', $refunds);

            $payment->metadata = $metadata;
        }

        $payment->refunded_amount += $refundAmount;

        if ($payment->refunded_amount == $payment->amount) {
            $payment->status = PaymentStatusEnum::REFUNDED;
        }

        $payment->refund_note = $request->input('refund_note');
        $payment->save();

        foreach ($request->input('products', []) as $productId => $quantity) {
            $product = Product::query()->find($productId);

            if ($product && $product->with_storehouse_management) {
                $product->quantity += $quantity;
                $product->save();
            }

            $orderProduct = OrderProduct::query()->where([
                'product_id' => $productId,
                'order_id' => $order->getKey(),
            ])
                ->first();

            if ($orderProduct) {
                $orderProduct->restock_quantity += $quantity;
                $orderProduct->save();
            }
        }

        if ($refundAmount > 0) {
            OrderHistory::query()->create([
                'action' => OrderHistoryActionEnum::REFUND,
                'description' => trans('plugins/ecommerce::order.refund_success_with_price', [
                    'price' => format_price($refundAmount),
                ]),
                'order_id' => $order->getKey(),
                'user_id' => Auth::id(),
                'extras' => json_encode([
                    'amount' => $refundAmount,
                    'method' => $payment->payment_channel ?? PaymentMethodEnum::COD,
                ]),
            ]);
        }

        $response->setMessage(trans('plugins/ecommerce::order.refund_success'));

        return apply_filters(ACTION_AFTER_POST_ORDER_REFUNDED_ECOMMERCE, $response, $order, $request);
    }

    public function getAvailableShippingMethods(Request $request, HandleShippingFeeService $shippingFeeService)
    {
        $weight = 0;
        $orderAmount = 0;

        foreach ($request->input('products', []) as $productId) {
            $product = Product::query()->find($productId);
            if ($product) {
                $weight += $product->weight * $product->qty;
                $orderAmount += $product->front_sale_price;
            }
        }

        $weight = EcommerceHelper::validateOrderWeight($weight);

        $shippingData = [
            'address' => $request->input('address'),
            'country' => $request->input('country'),
            'state' => $request->input('state'),
            'city' => $request->input('city'),
            'weight' => $weight,
            'order_total' => $orderAmount,
        ];

        $shipping = $shippingFeeService->execute($shippingData);

        $result = [];
        foreach ($shipping as $key => $shippingItem) {
            foreach ($shippingItem as $subKey => $subShippingItem) {
                $result[$key . ';' . $subKey . ';' . $subShippingItem['price']] = [
                    'name' => $subShippingItem['name'],
                    'price' => format_price($subShippingItem['price'], null, true),
                ];
            }
        }

        return $this
            ->httpResponse()
            ->setData($result);
    }

    public function postApplyCoupon(ApplyCouponRequest $request, HandleApplyCouponService $handleApplyCouponService)
    {
        $result = $handleApplyCouponService->applyCouponWhenCreatingOrderFromAdmin($request);

        if ($result['error']) {
            return $this
                ->httpResponse()
                ->setError()
                ->withInput()
                ->setMessage($result['message']);
        }

        return $this
            ->httpResponse()
            ->setData(Arr::get($result, 'data', []))
            ->setMessage(
                trans(
                    'plugins/ecommerce::order.applied_coupon_success',
                    ['code' => $request->input('coupon_code')]
                )
            );
    }

    public function getReorder(Request $request)
    {
        if (! $request->input('order_id')) {
            return $this
                ->httpResponse()
                ->setError()
                ->setNextUrl(route('orders.index'))
                ->setMessage(trans('plugins/ecommerce::order.order_is_not_existed'));
        }

        $this->pageTitle(trans('plugins/ecommerce::order.reorder'));

        Assets::usingVueJS();

        $order = Order::query()->find($request->input('order_id'));

        if (! $order) {
            return $this
                ->httpResponse()
                ->setError()
                ->setNextUrl(route('orders.index'))
                ->setMessage(trans('plugins/ecommerce::order.order_is_not_existed'));
        }

        $productIds = $order->products->pluck('product_id')->all();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get();

        $cartItems = collect();
        foreach ($order->products as $orderProduct) {
            $product = $products->firstWhere('id', $orderProduct->product_id);
            if (! $product) {
                continue;
            }

            $options = [
                'options' => $orderProduct->product_options,
            ];

            $cartItem = CartItem::fromAttributes($product->id, $orderProduct->product_name, 0, $options);
            $cartItem->setQuantity($orderProduct ? $orderProduct->qty : 1);

            $cartItems[] = $cartItem;
        }

        $products = CartItemResource::collection($cartItems);

        $customer = null;
        $customerAddresses = [];
        $customerOrderNumbers = 0;
        if ($order->user_id) {
            $customer = Customer::query()->findOrFail($order->user_id);
            $customer->avatar = (string) $customer->avatar_url;

            if ($customer) {
                $customerOrderNumbers = $customer->orders()->count();
            }

            $customerAddresses = CustomerAddressResource::collection($customer->addresses);
        }

        $customerAddress = new CustomerAddressResource($order->address);

        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly([
                'vendor/core/plugins/ecommerce/libraries/jquery.textarea_autosize.js',
                'vendor/core/plugins/ecommerce/js/order-create.js',
            ])
            ->addScripts(['input-mask']);

        return view(
            'plugins/ecommerce::orders.reorder',
            compact(
                'order',
                'products',
                'productIds',
                'customer',
                'customerAddresses',
                'customerAddress',
                'customerOrderNumbers'
            )
        );
    }

    public function getIncompleteList(OrderIncompleteTable $dataTable)
    {
        $this->pageTitle(trans('plugins/ecommerce::order.incomplete_order'));

        return $dataTable->renderTable();
    }

    public function getViewIncompleteOrder(Order $order)
    {
        $this->pageTitle(trans('plugins/ecommerce::order.incomplete_order'));

        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly([
                'vendor/core/plugins/ecommerce/libraries/jquery.textarea_autosize.js',
                'vendor/core/plugins/ecommerce/js/order-incomplete.js',
            ]);

        $order->load(['products', 'user']);

        $weight = number_format(EcommerceHelper::validateOrderWeight($order->products_weight));

        return view('plugins/ecommerce::orders.view-incomplete-order', compact('order', 'weight'));
    }

    public function markIncompleteOrderAsCompleted(Order $order)
    {
        DB::transaction(function () use ($order) {
            $order->update(['is_finished' => true]);

            $order->histories()->create([
                'order_id' => $order->getKey(),
                'user_id' => Auth::user()->getKey(),
                'action' => OrderHistoryActionEnum::MARK_ORDER_AS_COMPLETED,
                'description' => trans('plugins/ecommerce::order.mark_as_completed.history', [
                    'admin' => Auth::user()->name,
                    'time' => Carbon::now(),
                ]),
            ]);
        });

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::order.mark_as_completed.success'))
            ->setData([
                'next_url' => route('orders.edit', $order->getKey()),
            ]);
    }

    public function postSendOrderRecoverEmail(Order $order)
    {
        $email = $order->user->email ?: $order->address->email;

        if (! $email) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/ecommerce::order.error_when_sending_email'));
        }

        try {
            $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);

            $order->dont_show_order_info_in_product_list = true;
            OrderHelper::setEmailVariables($order);

            $mailer->sendUsingTemplate('order_recover', $email);

            return $this
                ->httpResponse()->setMessage(trans('plugins/ecommerce::order.sent_email_incomplete_order_success'));
        } catch (Exception $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function checkDataBeforeCreateOrder(Request $request)
    {
        $data = $this->getDataBeforeCreateOrder($request);

        return $this
            ->httpResponse()
            ->setData($data)
            ->setError(Arr::get($data, 'error', false))
            ->setMessage(implode('; ', Arr::get($data, 'message', [])));
    }

    protected function getDataBeforeCreateOrder(Request $request): array
    {
        if ($customerId = $request->input('customer_id')) {
            Discount::getFacadeRoot()->setCustomerId($customerId);
        }

        $with = [
            'productCollections',
            'variationInfo',
            'variationInfo.configurableProduct',
            'variationProductAttributes',
        ];
        if (is_plugin_active('marketplacce')) {
            $with = array_merge($with, ['store', 'variationInfo.configurableProduct.store']);
        }

        $inputProducts = collect($request->input('products'));
        if ($productIds = $inputProducts->pluck('id')->all()) {
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->with($with)
                ->get();
        } else {
            $products = collect();
        }

        $weight = 0;
        $discountAmount = 0;
        $shippingAmount = 0;
        $isError = false;
        $message = [];

        $cartItems = collect();
        $stores = collect();
        $productItems = collect();
        $addressKeys = ['name', 'company', 'address', 'country', 'state', 'city', 'zip_code', 'email', 'phone'];
        $addressTo = Arr::only($request->input('customer_address', []), $addressKeys);
        $country = Arr::get($addressTo, 'country');
        $state = Arr::get($addressTo, 'state');
        $city = Arr::get($addressTo, 'city');
        $zipCode = Arr::get($addressTo, 'zip_code');

        foreach ($inputProducts as $inputProduct) {
            $productId = $inputProduct['id'];
            $product = $products->firstWhere('id', $productId);
            if (! $product) {
                continue;
            }
            $productName = $product->original_product->name ?: $product->name;

            if ($product->isOutOfStock()) {
                $isError = true;
                $message[] = __('Product :product is out of stock!', ['product' => $productName]);
            }

            $productOptions = [];
            if ($inputOptions = Arr::get($inputProduct, 'options') ?: []) {
                $productOptions = OrderHelper::getProductOptionData($inputOptions);
            }

            $cartItemsById = $cartItems->where('id', $productId);

            $inputQty = Arr::get($inputProduct, 'quantity') ?: 1;
            $qty = $inputQty;
            $qtySelected = 0;
            if ($cartItemsById->count()) {
                $qtySelected = $cartItemsById->sum('qty');
            }

            $originalQuantity = $product->quantity;
            $product->quantity = (int) $product->quantity - $qtySelected - $inputQty + 1;

            if ($product->quantity < 0) {
                $product->quantity = 0;
            }

            if ($product->isOutOfStock()) {
                $isError = true;
                $qty = $originalQuantity - $qtySelected;
                if ($qty == 0) {
                    $message[] = __('Product :product is out of stock!', ['product' => $productName]);

                    continue;
                } else {
                    $message[] = __(
                        'Product :product limited quantity allowed is :quantity',
                        ['product' => $productName, 'quantity' => $qty]
                    );
                }
            }

            $product->quantity = $originalQuantity;

            if ($product->original_product->options->where('required', true)->count()) {
                if (! $inputOptions) {
                    $isError = true;
                    $message[] = __('Please select product options!');
                } else {
                    $requiredOptions = $product->original_product->options->where('required', true);

                    foreach ($requiredOptions as $requiredOption) {
                        if (! Arr::get($inputOptions, $requiredOption->id . '.values')) {
                            $isError = true;
                            $message[] = trans(
                                'plugins/ecommerce::product-option.add_to_cart_value_required',
                                ['value' => $requiredOption->name]
                            );
                        }
                    }
                }
            }

            if (is_plugin_active('marketplace')) {
                $store = $product->original_product->store;
                if ($store->id) {
                    $productName .= ' (' . $store->name . ')';
                }
                $stores[] = $store;
            }

            $parentProduct = $product->original_product;

            $image = $product->image ?: $parentProduct->image;
            $taxRate = app(HandleTaxService::class)->taxRate($parentProduct, $country, $state, $city, $zipCode);
            $options = [
                'name' => $productName,
                'image' => $image,
                'attributes' => $product->is_variation ? $product->variation_attributes : '',
                'taxRate' => $taxRate,
                'options' => $productOptions,
                'extras' => [],
                'sku' => $product->sku,
                'weight' => $product->original_product->weight,
                'original_price' => $product->front_sale_price,
                'product_link' => route('products.edit', $product->original_product->id),
                'product_type' => (string) $product->product_type,
            ];

            $price = $product->front_sale_price;
            $price = Cart::getPriceByOptions($price, $productOptions);

            $cartItem = CartItem::fromAttributes(
                $product->id,
                BaseHelper::clean($parentProduct->name ?: $product->name),
                $price,
                $options
            );

            $cartItemExists = $cartItems->firstWhere('rowId', $cartItem->rowId);

            if (! $cartItemExists) {
                $cartItem->setQuantity($qty);
                $cartItem->setTaxRate($taxRate);

                $cartItems[] = $cartItem;
                if (! $product->isTypeDigital()) {
                    $weight += $product->original_product->weight * $qty;
                }
                $product->cartItem = $cartItem;
                $productItems[] = $product;
            }
        }

        if (is_plugin_active('marketplace')) {
            if (count(array_unique(array_filter($stores->pluck('id')->all()))) > 1) {
                $isError = true;
                $message[] = trans('plugins/marketplace::order.products_are_from_different_vendors');
            }
        }

        $subAmount = Cart::rawSubTotalByItems($cartItems);
        $taxAmount = Cart::rawTaxByItems($cartItems);
        $rawTotal = Cart::rawTotalByItems($cartItems);

        $cartData = [];

        Arr::set($cartData, 'rawTotal', $rawTotal);
        Arr::set($cartData, 'cartItems', $cartItems);
        Arr::set($cartData, 'countCart', Cart::countByItems($cartItems));
        Arr::set($cartData, 'productItems', $productItems);

        $isAvailableShipping = $productItems->count() && EcommerceHelper::isAvailableShipping($productItems);

        $weight = EcommerceHelper::validateOrderWeight($weight);

        $shippingMethods = [];

        if ($isAvailableShipping) {
            $origin = EcommerceHelper::getOriginAddress();

            if (is_plugin_active('marketplace')) {
                if ($stores->count() && ($store = $stores->first()) && $store->id) {
                    $origin = Arr::only($store->toArray(), $addressKeys);
                    if (! EcommerceHelper::isUsingInMultipleCountries()) {
                        $origin['country'] = EcommerceHelper::getFirstCountryId();
                    }
                }
            }

            $items = [];
            foreach ($productItems as $product) {
                if (! $product->isTypeDigital()) {
                    $cartItem = $product->cartItem;
                    $items[$cartItem->rowId] = [
                        'weight' => $product->weight,
                        'length' => $product->length,
                        'width' => $product->width,
                        'height' => $product->height,
                        'depth' => $product->depth,
                        'shipping_width' => $product->shipping_width,
                        'shipping_length' => $product->shipping_length,
                        'shipping_height' => $product->shipping_height,
                        'shipping_depth' => $product->shipping_depth,

                        'name' => $product->name,
                        'description' => $product->description,
                        'qty' => $cartItem->qty,
                        'price' => $product->front_sale_price,
                    ];
                }
            }

            $shippingData = [
                'address' => Arr::get($addressTo, 'address'),
                'country' => $country,
                'state' => $state,
                'city' => $city,
                'weight' => $weight,
                'order_total' => $rawTotal,
                'address_to' => $addressTo,
                'origin' => $origin,
                'items' => $items,
                'extra' => [],
                'payment_method' => $request->input('payment_method'),
            ];

            $shipping = $this->shippingFeeService->execute($shippingData);

            foreach ($shipping as $key => $shippingItem) {
                foreach ($shippingItem as $subKey => $subShippingItem) {
                    $shippingMethods[$key . ';' . $subKey] = [
                        'name' => $subShippingItem['name'],
                        'price' => format_price($subShippingItem['price'], null, true),
                        'price_label' => format_price($subShippingItem['price']),
                        'method' => $key,
                        'option' => $subKey,
                        'title' => $subShippingItem['name'] . ' - ' . format_price($subShippingItem['price']),
                        'id' => Arr::get($subShippingItem, 'id'),
                        'shipment_id' => Arr::get($subShippingItem, 'shipment_id'),
                        'company_name' => Arr::get($subShippingItem, 'company_name'),
                    ];
                }
            }
        }

        $shippingMethodName = '';
        $shippingMethod = $request->input('shipping_method');
        $shippingOption = $request->input('shipping_option');
        $shippingType = $request->input('shipping_type');
        $shipping = [];

        if ($shippingType == 'free-shipping') {
            $shippingMethodName = trans('plugins/ecommerce::order.free_shipping');
            $shippingMethod = 'default';
        } else {
            if ($shippingMethod && $shippingOption) {
                if ($shipping = Arr::get($shippingMethods, $shippingMethod . ';' . $shippingOption)) {
                    $shippingAmount = Arr::get($shipping, 'price') ?: 0;
                    $shippingMethodName = Arr::get($shipping, 'name');
                }
            }
            if (! $shippingMethodName) {
                if ($shipping = Arr::first($shippingMethods)) {
                    $shippingAmount = Arr::get($shipping, 'price') ?: 0;
                    $shippingMethodName = Arr::get($shipping, 'name');
                }
            }
            if (! $shippingMethodName) {
                $shippingMethod = 'default';
                $shippingOption = '';
            }
        }

        $promotionAmount = $this->applyPromotionsService->getPromotionDiscountAmount($cartData);

        Arr::set($cartData, 'promotion_discount_amount', $promotionAmount);

        if ($couponCode = trim($request->input('coupon_code'))) {
            $couponData = $this->handleApplyCouponService->applyCouponWhenCreatingOrderFromAdmin($request, $cartData);
            if (Arr::get($couponData, 'error')) {
                $isError = true;
                $message[] = Arr::get($couponData, 'message');
            } else {
                if (Arr::get($couponData, 'data.is_free_shipping')) {
                    $shippingAmount = 0;
                } else {
                    $discountAmount = Arr::get($couponData, 'data.discount_amount');
                    if (! $discountAmount) {
                        $isError = true;
                        $message[] = __('Coupon code is not valid or does not apply to the products');
                    }
                }
            }
        } else {
            $couponData = [];
            if ($discountCustomValue = max((float) $request->input('discount_custom_value'), 0)) {
                if ($request->input('discount_type') === 'percentage') {
                    $discountAmount = $rawTotal * min($discountCustomValue, 100) / 100;
                } else {
                    $discountAmount = $discountCustomValue;
                }
            }
        }

        $totalAmount = max($rawTotal - $promotionAmount - $discountAmount, 0) + $shippingAmount;

        $data = [
            'customer_id' => $customerId,
            'products' => CartItemResource::collection($cartItems),
            'shipping_methods' => $shippingMethods,
            'weight' => $weight,
            'promotion_amount' => $promotionAmount,
            'promotion_amount_label' => format_price($promotionAmount),
            'discount_amount' => $discountAmount,
            'discount_amount_label' => format_price($discountAmount),
            'sub_amount' => $subAmount,
            'sub_amount_label' => format_price($subAmount),
            'tax_amount' => $taxAmount,
            'tax_amount_label' => format_price($taxAmount),
            'shipping_amount' => $shippingAmount,
            'shipping_amount_label' => format_price($shippingAmount),
            'total_amount' => $totalAmount,
            'total_amount_label' => format_price($totalAmount),
            'coupon_data' => $couponData,
            'shipping' => $shipping,
            'shipping_method_name' => $shippingMethodName,
            'shipping_type' => $shippingType,
            'shipping_method' => $shippingMethod,
            'shipping_option' => $shippingOption,
            'coupon_code' => $couponCode,
            'is_available_shipping' => $isAvailableShipping,
            'update_context_data' => true,
            'error' => $isError,
            'message' => $message,
        ];

        if (is_plugin_active('marketplace')) {
            $data['store'] = $stores->first() ?: [];
        }

        return $data;
    }

    public function generateInvoice(Order $order)
    {
        if ($order->isInvoiceAvailable()) {
            abort(404);
        }

        InvoiceHelper::store($order);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::order.generated_invoice_successfully'));
    }

    public function downloadProof(Order $order)
    {
        if (! $order->proof_file) {
            abort(404);
        }

        $storage = Storage::disk('local');

        if (! $storage->exists($order->proof_file)) {
            abort(404);
        }

        return $storage->download($order->proof_file);
    }
}
