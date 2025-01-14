<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentApiController extends Controller
{
    public function createPayment(Request $request)
    {
        // Validate the request data
        $request->validate([
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'description' => 'required|string',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
        ]);

        // Prepare order data
        $number = 'order-' . uniqid();
        $amount = number_format($request->amount, 2, '.', '');  // Ensure two decimal places
        $currency = strtoupper($request->currency);
        $description = $request->description;

        // Merchant credentials
        $merchantKey = env('TOTALPAY_MERCHANT_KEY');
        $merchantPassword = env('TOTALPAY_MERCHANT_PASSWORD');

        // Generate hash based on documentation requirements
        $hash = sha1(md5(strtoupper($number . $amount . $currency . $description . $merchantPassword)));
        Log::info('Generated hash: ' . $hash);

        // Customer info
        $customerinfo = [
            'name' => $request->customer_name,
            'email' => $request->customer_email,
        ];

        // Billing address (dummy values here; replace with dynamic if necessary)
        $billing_address = [
            'country' => 'AE',
            'state' => 'Dubai',
            'city' => 'Dubai',
            'address' => 'Dubai',
            'phone' => '0505050505',
        ];

        // Order JSON data
        $order_json = [
            'number' => $number,
            'description' => $description,
            'amount' => $amount,
            'currency' => $currency,
        ];

        // TotalPay API request data, including required parameters
        $main_json = [
            'merchant_key' => $merchantKey,
            'operation' => 'purchase',
            // 'success_url' => route('payment.success'),
            'success_url' => 'https://thehorecastore.co/payment/success',
            'success_url' => 'https://thehorecastore.co/payment/cancel',
            // 'cancel_url' => route('payment.cancel'),
            'hash' => $hash,
            'order' => $order_json,
            'customer' => $customerinfo,
            'billing_address' => $billing_address,
          

            // Remove methods key to let the API determine acceptable methods
        ];
        
        

        // Send the request to TotalPay API
        $checkoutUrl = "https://checkout.totalpay.global/api/v1/session";
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($checkoutUrl, $main_json);

        if ($response->successful()) {
            $cRes = $response->json();
            Log::info('TotalPay Response: ' . json_encode($cRes));
            return response()->json([
                'status' => 'success',
                'redirect_url' => $cRes['redirect_url'],
            ]);
        } else {
            Log::error('Payment request failed with status: ' . $response->status());
            Log::error('Response Body: ' . $response->body());
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong with the payment request.',
            ], 500);
        }
    }

    public function paymentSuccess()
    {
        return response()->json(['status' => 'success', 'message' => 'Payment Successful']);
    }

    public function paymentCancel()
    {
        return response()->json(['status' => 'error', 'message' => 'Payment Cancelled']);
    }
}
