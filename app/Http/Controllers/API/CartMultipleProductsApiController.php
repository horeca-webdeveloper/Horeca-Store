<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Botble\Ecommerce\Models\Cart;

class CartMultipleProductsApiController extends Controller
{
    // Method to add multiple products to the cart
//     public function addMultipleToCart(Request $request)
// {
    
    
//     // Validate the request input
//     $request->validate([
//         'products' => 'required|array',
//         'products.*.product_id' => 'required|exists:ec_products,id',
//         'products.*.quantity' => 'required|integer|min:1',
//     ]);

//     $products = $request->input('products');
//     $userId = Auth::check() ? Auth::id() : null; // Get authenticated user ID
//     $sessionId = $userId ? null : $request->session()->getId(); // Get session ID for guests

//     foreach ($products as $item) {
//         $productId = $item['product_id'];
//         $quantity = $item['quantity'];

//         // Query to find existing cart item
//         $cartItem = Cart::where(function($query) use ($userId, $sessionId) {
//             if ($userId) {
//                 $query->where('user_id', $userId);
//             } else {
//                 $query->where('session_id', $sessionId);
//             }
//         })
//         ->where('product_id', $productId)
//         ->first();

//         if ($cartItem) {
//             // Update quantity if item already in cart
//             $cartItem->quantity += $quantity;
//             $cartItem->save();
//         } else {
//             // Create new cart item
//             Cart::create([
//                 'user_id' => $userId,
//                 'session_id' => $sessionId,
//                 'product_id' => $productId,
//                 'quantity' => $quantity,
//             ]);
//         }
//     }

//     // Fetch the current cart items
//     $cartItems = Cart::where(function($query) use ($userId, $sessionId) {
//         if ($userId) {
//             $query->where('user_id', $userId);
//         } else {
//             $query->where('session_id', $sessionId);
//         }
//     })->with('product') // Assuming you have a relation to fetch product details
//       ->get();

//     return response()->json([
//         'success' => true,
//         'message' => 'Products added to cart',
//         'cart' => $cartItems // Include the current cart items in the response
//     ]);
// }


    // Method to add multiple products to the cart
    // public function addMultipleToCart(Request $request)
    // {
    //     // Start the session for guests explicitly if not started
    //     if (!session()->isStarted()) {
    //         session()->start();
    //     }

    //     // Validate the request input
    //     $request->validate([
    //         'products' => 'required|array',
    //         'products.*.product_id' => 'required|exists:ec_products,id',
    //         'products.*.quantity' => 'required|integer|min:1',
    //     ]);

    //     $products = $request->input('products');
    //     $userId = Auth::check() ? Auth::id() : null; // Get authenticated user ID
    //     $sessionId = $userId ? null : session()->getId(); // Get session ID for guests

    //     foreach ($products as $item) {
    //         $productId = $item['product_id'];
    //         $quantity = $item['quantity'];

    //         // Query to find existing cart item
    //         $cartItem = Cart::where(function ($query) use ($userId, $sessionId) {
    //             if ($userId) {
    //                 $query->where('user_id', $userId);
    //             } else {
    //                 $query->where('session_id', $sessionId);
    //             }
    //         })
    //         ->where('product_id', $productId)
    //         ->first();

    //         if ($cartItem) {
    //             // Update quantity if item already in cart
    //             $cartItem->quantity += $quantity;
    //             $cartItem->save();
    //         } else {
    //             // Create new cart item
    //             Cart::create([
    //                 'user_id' => $userId,
    //                 'session_id' => $sessionId,
    //                 'product_id' => $productId,
    //                 'quantity' => $quantity,
    //             ]);
    //         }
    //     }

    //     // Fetch the current cart items
    //     $cartItems = Cart::where(function ($query) use ($userId, $sessionId) {
    //         if ($userId) {
    //             $query->where('user_id', $userId);
    //         } else {
    //             $query->where('session_id', $sessionId);
    //         }
    //     })->with('product') // Assuming you have a relation to fetch product details
    //       ->get();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Products added to cart',
    //         'cart' => $cartItems // Include the current cart items in the response
    //     ]);
    // }
public function addMultipleToCart(Request $request)
{
    // Validate the request input
    $request->validate([
        'products' => 'required|array',
        'products.*.product_id' => 'required|exists:ec_products,id',
        'products.*.quantity' => 'required|integer|min:1',
    ]);

    $products = $request->input('products');
    $userId = Auth::check() ? Auth::id() : null; // Get authenticated user ID
    $sessionId = $userId ? null : $request->session()->getId(); // Get session ID for guests

    foreach ($products as $item) {
        $productId = $item['product_id'];
        $quantity = $item['quantity'];

        // Query to find existing cart item
        $cartItem = Cart::where(function($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })
        ->where('product_id', $productId)
        ->first();

        if ($cartItem) {
            // Update quantity if item already in cart
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            // Create new cart item
            Cart::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }
    }

    // Fetch the current cart items
    $cartItems = Cart::where(function($query) use ($userId, $sessionId) {
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }
    })->with('product')
      ->get();

    return response()->json([
        'success' => true,
        'message' => 'Products added to cart',
        'cart' => $cartItems
    ]);
}



}
