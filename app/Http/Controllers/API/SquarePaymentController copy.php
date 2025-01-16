<?php

// namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Services\SquareService;
// use Square\SquareClient;
// use Square\Models\CreatePaymentRequest;
// use Square\Models\Money;
// use Square\Exceptions\ApiException;

// class SquarePaymentController extends Controller
// {
//     private $squareClient;

//     public function __construct()
//     {
//         $this->squareClient = new SquareClient([
//             'accessToken' => env('SQUARE_ACCESS_TOKEN'),
//             'environment' => env('SQUARE_ENV', 'sandbox'),
//         ]);
//     }
//     public function createPayment(Request $request)
// {
//     $request->validate([
//         'nonce' => 'required|string',
//         'amount' => 'required|numeric|min:0.5',
//         'currency' => 'required|string|size:3',
//     ]);

//     $nonce = $request->input('nonce');
//     $amount = $request->input('amount');
//     $currency = strtoupper($request->input('currency'));

//     try {
//         $paymentsApi = $this->squareClient->getPaymentsApi();

//         // Convert amount from dollars to cents
//         $amountInCents = (int) ($amount * 100);

//         // Create the Money object
//         $money = new Money();
//         $money->setAmount($amountInCents); // Amount in cents
//         $money->setCurrency($currency);

//         // Ensure the location ID is passed as a parameter here
//         $paymentRequest = new CreatePaymentRequest($nonce, env('SQUARE_LOCATION_ID'), $money);

//         // Send the payment request
//         $response = $paymentsApi->createPayment($paymentRequest);

//         if ($response->isSuccess()) {
//             return response()->json([
//                 'success' => true,
//                 'payment' => $response->getResult()->getPayment(),
//             ]);
//         } else {
//             return response()->json([
//                 'success' => false,
//                 'errors' => $response->getErrors(),
//             ], 400);
//         }
//     } catch (ApiException $e) {
//         return response()->json([
//             'success' => false,
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// }

    
//     public function paymentForm()
// {
//     return view('payment.form', [
//         'square_application_id' => env('SQUARE_APPLICATION_ID'),
//     ]);
// }

// }

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Square\SquareClient;
use Square\Models\Money;
use Square\Models\CreatePaymentRequest;
use Square\Models\OfflinePaymentDetails;
use Square\Exceptions\ApiException;

class SquarePaymentController extends Controller
{
    private $squareClient;

    public function __construct()
    {
        $this->squareClient = new SquareClient([
            'accessToken' => env('SQUARE_ACCESS_TOKEN'),
            'environment' => env('SQUARE_ENV', 'sandbox'),
        ]);
    }

    public function createPayment(Request $request)
    {
        // Validate input
        $request->validate([
            'nonce' => 'required|string',
            'amount' => 'required|numeric|min:0.5',
            'currency' => 'required|string|size:3',
            'customer_id' => 'required|string',
            'location_id' => 'required|string',
            'team_member_id' => 'required|string',
            'buyer_email_address' => 'required|email',
        ]);

        $nonce = $request->input('nonce');
        $amount = $request->input('amount');
        $currency = strtoupper($request->input('currency'));
        $customerId = $request->input('customer_id');
        $locationId = $request->input('location_id');
        $teamMemberId = $request->input('team_member_id');
        $buyerEmailAddress = $request->input('buyer_email_address');

        try {
            $paymentsApi = $this->squareClient->getPaymentsApi();

            // Create the Money object for the payment amount
            $amountMoney = new Money();
            $amountMoney->setAmount((int)($amount * 100)); // Convert amount to cents
            $amountMoney->setCurrency($currency);

            // Create Offline Payment Details (if applicable)
            $offlinePaymentDetails = new OfflinePaymentDetails();

            // Create the payment request with necessary parameters
            $paymentRequest = new CreatePaymentRequest(
                $nonce,  // Nonce
                uniqid('payment_')  // Idempotency key for uniqueness
            );

            // Set amount_money using the Money object
            $paymentRequest->setAmountMoney($amountMoney);

            // Set additional fields
            $paymentRequest->setCustomerId($customerId);
            $paymentRequest->setLocationId($locationId);
            $paymentRequest->setTeamMemberId($teamMemberId);
            $paymentRequest->setReferenceId(uniqid('ref_'));  // Optional: Unique reference ID
            $paymentRequest->setAcceptPartialAuthorization(true);  // Accept partial authorization if needed
            $paymentRequest->setBuyerEmailAddress($buyerEmailAddress);
            $paymentRequest->setOfflinePaymentDetails($offlinePaymentDetails); // If needed

            // Send the payment request to Square API
            $response = $paymentsApi->createPayment($paymentRequest);

            if ($response->isSuccess()) {
                return response()->json([
                    'success' => true,
                    'payment' => $response->getResult()->getPayment(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'errors' => $response->getErrors(),
                ], 400);
            }
        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentForm()
    {
        return view('payment.form', [
            'square_application_id' => env('SQUARE_APPLICATION_ID'),
        ]);
    }
}