<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Botble\Ecommerce\Models\Cart;
use Botble\Ecommerce\Models\SaveForLater;
use Illuminate\Support\Facades\DB;

class SaveForLaterController extends Controller
{
    public function saveForLater(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'product_id' => 'required|exists:ec_products,id', // Updated to match the table name
        ]);

        // Get the logged-in user
        $user = Auth::user();

        // Check if the product exists in the user's cart
        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $request->product_id)
                        ->first();

        if (!$cartItem) {
            return response()->json([
                'message' => 'Product not found in cart.'
            ], 404);
        }

        // Add the product to the Save for Later table
        SaveForLater::updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $request->product_id,
            ],
            [
                'quantity' => $cartItem->quantity,
            ]
        );

        // Remove the product from the cart
        $cartItem->delete();

        return response()->json([
            'message' => 'Product has been moved to Save for Later.',
        ], 200);
    }
    //  public function showSaveForLater()
    // {
    //     // Get the logged-in user
    //     $user = Auth::user();

    //     // Fetch all saved products for the user
    //     $savedProducts = SaveForLater::where('user_id', $user->id)
    //                                  ->with('product')  // Assuming `product` is the relationship
    //                                  ->get();

    //     if ($savedProducts->isEmpty()) {
    //         return response()->json([
    //             'message' => 'No products saved for later.'
    //         ], 404);
    //     }

    //     // Return the saved products data
    //     $productsData = $savedProducts->map(function ($item) {
    //         return $item->product; // Return product data associated with the saved product
    //     });

    //     return response()->json([
    //         'message' => 'Saved for Later Products retrieved successfully.',
    //         'product' => $productsData
    //     ], 200);
    // }
    
    public function showSaveForLater(Request $request)
{
    // Get the logged-in user
    $user = Auth::user();

    // Fetch all saved products for the user
    $savedProducts = SaveForLater::where('user_id', $user->id)
                                 ->with('product')  // Assuming `product` is the relationship
                                 ->get();

    if ($savedProducts->isEmpty()) {
        return response()->json([
            'message' => 'No products saved for later.'
        ], 404);
    }

    // Return the saved products data
    $productsData = $savedProducts->map(function ($item) {
        $product = $item->product; // Get the product

        // Calculate the total reviews and average rating
        $totalReviews = $product->reviews->count();
        $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
        $product->total_reviews = $totalReviews;
        $product->avg_rating = $avgRating;

        // Add currency details
        if ($product->currency) {
            $product->currency_title = $product->currency->is_prefix_symbol
                ? $product->currency->title
                : $product->price . ' ' . $product->currency->title;
        } else {
            $product->currency_title = $product->price; // Fallback if no currency found
        }

        return $product; // Return the modified product data
    });

    return response()->json([
        'message' => 'Saved for Later Products retrieved successfully.',
        'product' => $productsData
    ], 200);
}

public function removeFromSaveForLater(Request $request)
{
    // Validate the incoming request
    $request->validate([
        'product_id' => 'required|exists:ec_products,id', // Ensure the product exists in the product table
    ]);

    // Get the logged-in user
    $user = Auth::user();

    // Check if the product exists in the "Save for Later" list
    $savedProduct = SaveForLater::where('user_id', $user->id)
                               ->where('product_id', $request->product_id)
                               ->first();

    if (!$savedProduct) {
        return response()->json([
            'message' => 'Product not found in Save for Later.'
        ], 404);
    }

    // Remove the product from the Save for Later table
    $savedProduct->delete();

    return response()->json([
        'message' => 'Product has been removed from Save for Later.'
    ], 200);
}


}
