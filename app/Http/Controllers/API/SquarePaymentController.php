<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SquareService;

class SquarePaymentController extends Controller
{
    protected $squareService;

    public function __construct(SquareService $squareService)
    {
        $this->squareService = $squareService;
    }

    public function processPayment(Request $request)
    {
        // Validate request data
        $request->validate([
            'nonce' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        // Retrieve parameters from the request
        $nonce = $request->input('nonce');
        $amount = $request->input('amount');

        // Process payment using the Square service
        $response = $this->squareService->processPayment($nonce, $amount);

        return response()->json($response);
    }
}
