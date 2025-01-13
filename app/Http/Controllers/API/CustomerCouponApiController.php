<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Discount;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CustomerCouponApiController extends Controller
{
    /**
     * Fetch all coupons and discounts available for the logged-in customer.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerCoupons(Request $request)
    {
        // Get the authenticated user ID
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        // Fetch discounts linked to the customer from the ec_discount_customers table
        $discounts = Discount::whereHas('customers', function ($query) use ($userId) {
            $query->where('customer_id', $userId);
        })
        ->where(function ($query) {
            // Check if the discount is still valid
            $query->where('end_date', '>=', Carbon::now())
                  ->orWhereNull('end_date');
        })
        ->get();

        if ($discounts->isEmpty()) {
            return response()->json(['message' => 'No coupons or discounts available for this customer.'], 404);
        }

        // Format response with necessary details
        $coupons = $discounts->map(function ($discount) {
            return [
                'id' => $discount->id,
                'code' => $discount->code,
                'value' => $discount->value,
                'type' => $discount->type, // e.g., percentage or fixed amount
                'min_order_price' => $discount->min_order_price,
                'start_date' => $discount->start_date,
                'end_date' => $discount->end_date,
            ];
        });

        return response()->json(['coupons' => $coupons]);
    }
}
