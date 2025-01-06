<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Botble\Ecommerce\Models\Cart;
use Botble\Ecommerce\Models\ShippingRule;
use App\Http\Controllers\Controller;

class CartSummaryController extends Controller
{
    public function cartSummary(Request $request)
    {
        // Determine if the user is logged in
        $userId = Auth::id();
        $sessionId = $userId ? null : $request->session()->getId();

        // Fetch cart items with product details and currency information
        $cartItems = Cart::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->with('product.currency')->get();

        // Initialize summary variables
        $subtotal = 0;
        $total = 0;
        $savings = 0;
        $currencyTitle = $cartItems->first()->product->currency->title ?? 'USD'; // Default to 'USD' if no currency found

        foreach ($cartItems as $item) {
            // Use sale_price if available, otherwise use price
            $itemPrice = ($item->product->sale_price && $item->product->sale_price > 0)
                ? $item->product->sale_price
                : $item->product->price;

            $subtotal += $item->quantity * $itemPrice;
            $total += $item->quantity * $item->product->price;
        }

        $savings = $total - $subtotal;

        // Calculate tax and total including tax
        $tax = $subtotal * 0.10;
        $totalWithTax = $subtotal + $tax;

        // Fetch applicable shipping rate
        $shippingRate = 0;
        $shippingRule = ShippingRule::where('from', '<=', $subtotal)
            ->where(function ($query) use ($subtotal) {
                $query->where('to', '>=', $subtotal)
                      ->orWhereNull('to');
            })
            ->first();

        if ($shippingRule) {
            $shippingRate = $shippingRule->price;
        }

        // Return the cart summary with currency title
        return response()->json([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total_with_tax' => $totalWithTax,
            'shipping_rate' => $shippingRate,
            'total_with_shipping' => $totalWithTax + $shippingRate,
            'savings' => $savings,
            'item_count' => $cartItems->count(),
            'currency_title' => $currencyTitle,
        ]);
    }
}

// namespace App\Http\Controllers\API;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Botble\Ecommerce\Models\Cart;
// use Botble\Ecommerce\Models\ShippingRule;
// use App\Http\Controllers\Controller;

// class CartSummaryController extends Controller
// {
//     public function cartSummary(Request $request)
//     {
//         // Determine if the user is logged in
//         $userId = Auth::id();
//         $sessionId = $userId ? null : $request->session()->getId();

//         // Fetch cart items with product details for the logged-in user or guest
//         $cartItems = Cart::where(function ($query) use ($userId, $sessionId) {
//             if ($userId) {
//                 $query->where('user_id', $userId);
//             } else {
//                 $query->where('session_id', $sessionId);
//             }
//         })->get();

//         // Calculate subtotal, total, and savings
//         $subtotal = 0;
//         $total = 0;
//         $savings = 0;

//         foreach ($cartItems as $item) {
//             // Use sale_price if it's not null or zero, otherwise use the price
//             $itemPrice = ($item->product->sale_price && $item->product->sale_price > 0)
//                 ? $item->product->sale_price
//                 : $item->product->price;
            
//             $subtotal += $item->quantity * $itemPrice;

//             // Similarly, calculate the total (if necessary, based on regular price)
//             $total += $item->quantity * $item->product->price;
//         }

//         $savings = $total - $subtotal;

//         // Calculate 10% tax on the subtotal
//         $tax = $subtotal * 0.10;

//         // Calculate the final total including tax
//         $totalWithTax = $subtotal + $tax;

//         // Find the applicable shipping rate from ec_shipping_rules
//         $shippingRate = 0;
//         $shippingRule = ShippingRule::where('from', '<=', $subtotal)
//             ->where(function ($query) use ($subtotal) {
//                 $query->where('to', '>=', $subtotal)
//                       ->orWhereNull('to'); // Include records where 'to' is null
//             })
//             ->first();

//         if ($shippingRule) {
//             $shippingRate = $shippingRule->price;
//         }

//         // Return the cart summary
//         return response()->json([
//             'subtotal' => $subtotal,
//             'tax' => $tax,
//             'total_with_tax' => $totalWithTax,
//             'shipping_rate' => $shippingRate,
//             'total_with_shipping' => $totalWithTax + $shippingRate,
//             'savings' => $savings,
//             'item_count' => $cartItems->count(),
//         ]);
//     }
// }
