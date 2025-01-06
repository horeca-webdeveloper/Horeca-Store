<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\RecentlyViewedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecentlyViewedProductController extends Controller
{
    public function addToRecent(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:ec_products,id', // Validates if the product exists
        ]);

        $productId = $request->input('product_id');
        $userId = Auth::id(); // Use Auth if the user is logged in

        if ($userId) {
            // Add the product to the recently viewed list
            RecentlyViewedProduct::updateOrCreate(
                ['customer_id' => $userId, 'product_id' => $productId],
                ['updated_at' => now()] // Updates the timestamp if already exists
            );

            return response()->json(['message' => 'Product added to recently viewed list.'], 200);
        }

        return response()->json(['message' => 'User not authenticated.'], 401);
    }

    public function getRecentProducts()
    {
        $userId = Auth::id(); // Get authenticated user

        if ($userId) {
            // Fetch recently viewed products for the logged-in user, eager load the related product data
            $recentlyViewed = RecentlyViewedProduct::with('product') // Ensure 'product' relationship is loaded
                ->where('customer_id', $userId)
                ->latest()  // Order by most recently viewed
                ->take(5)   // Limit to the last 5 viewed products
                ->get();

            // Check if we have any recently viewed products
            if ($recentlyViewed->isEmpty()) {
                return response()->json(['message' => 'No recently viewed products found.'], 404);
            }

            return response()->json($recentlyViewed);
        }

        return response()->json(['message' => 'User not authenticated.'], 401);
    }
}
