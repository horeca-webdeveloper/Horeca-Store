<?php

namespace App\Services;

use Square\SquareClient;
use Square\Models\Money;
use Square\Models\CreatePaymentRequest;
use Square\Environment;
use Square\Exceptions\ApiException;

class SquareService
{
    protected $client;

    public function __construct()
    {
        $this->client = new SquareClient([
            'accessToken' => env('SQUARE_ACCESS_TOKEN'),
            'environment' => env('SQUARE_ENVIRONMENT', Environment::SANDBOX),
        ]);
    }

    public function processPayment($nonce, $amount)
    {
        $paymentsApi = $this->client->getPaymentsApi();

        // Create a Money object to represent the amount
        $money = new Money();
        $money->setAmount((int)($amount * 100)); // Convert dollars to cents
        $money->setCurrency('USD'); // Set the appropriate currency

        // Create a unique idempotency key
        $idempotencyKey = uniqid();

        // Create a payment request
        $createPaymentRequest = new CreatePaymentRequest($nonce, $idempotencyKey);
        $createPaymentRequest->setAmountMoney($money); // Set the amount_money field

        try {
            // Execute the payment request
            $response = $paymentsApi->createPayment($createPaymentRequest);

            if ($response->isSuccess()) {
                return [
                    'success' => true,
                    'data' => $response->getResult(),
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => $response->getErrors(),
                ];
            }
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
