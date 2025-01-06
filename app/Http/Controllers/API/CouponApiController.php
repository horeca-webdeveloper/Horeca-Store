<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Models\DiscountCustomer;
use Botble\Ecommerce\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;



class CouponApiController extends Controller
{
 public function applyCoupon(Request $request)
{
    $validated = $request->validate([
        'coupon_code' => 'required|string',
        'total_order_price' => 'required|numeric|min:0',
    ]);

    // Retrieve coupon by code
    $coupon = Discount::where('code', $request->coupon_code)->first();

    if (!$coupon) {
        return response()->json(['message' => 'Coupon code not found.'], 400);
    }

    // Check expiration
    if ($coupon->isExpired()) {
        return response()->json(['message' => 'Coupon has expired.'], 400);
    }

    // Minimum order price check
    if ($coupon->min_order_price && $request->total_order_price < $coupon->min_order_price) {
        return response()->json(['message' => 'Order price is below the minimum required for this coupon.'], 400);
    }

    // Usage limit check
    if ($coupon->quantity && $coupon->total_used >= $coupon->quantity) {
        return response()->json(['message' => 'Coupon has already been used up.'], 400);
    }

    // Calculate discount based on the coupon value
    $discountAmount = $coupon->value; // value is just a number (fixed discount)

    // Ensure the discount doesn't exceed the total order price
    $discountAmount = min($discountAmount, $request->total_order_price);

    // Update coupon usage count
    $coupon->increment('total_used');

    // Calculate the final price after applying the discount
    $finalPrice = $request->total_order_price - $discountAmount;

    // Ensure the final price is not less than zero
    $finalPrice = max($finalPrice, 0);

    return response()->json([
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,
    ]);
}



}

