<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\RecentlyViewedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


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

    // public function getRecentProducts()
    // {
    //     $userId = Auth::id(); // Get authenticated user

    //     if ($userId) {
    //         // Fetch recently viewed products for the logged-in user, eager load the related product data
    //         $recentlyViewed = RecentlyViewedProduct::with('product') // Ensure 'product' relationship is loaded
    //             ->where('customer_id', $userId)
    //             ->latest()  // Order by most recently viewed
    //             ->take(5)   // Limit to the last 5 viewed products
    //             ->get();

    //         // Check if we have any recently viewed products
    //         if ($recentlyViewed->isEmpty()) {
    //             return response()->json(['message' => 'No recently viewed products found.'], 404);
    //         }

    //         return response()->json($recentlyViewed);
    //     }

    //     return response()->json(['message' => 'User not authenticated.'], 401);
    // }


    private function getWishlistProductIds()
    {
        $userId = Auth::id();

        if ($userId) {
            return Cache::remember("wishlist_user_{$userId}", 60, function () use ($userId) {
                return DB::table('ec_wish_lists')
                    ->where('customer_id', $userId)
                    ->pluck('product_id')
                    ->toArray();
            });
        }

        return session()->get('guest_wishlist', []);
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

        // Get wishlist product IDs
        $wishlistIds = $this->getWishlistProductIds();

        // Check if we have any recently viewed products
        if ($recentlyViewed->isEmpty()) {
            return response()->json(['message' => 'No recently viewed products found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $recentlyViewed->map(function ($viewed) use ($wishlistIds) {
                $product = $viewed->product;
        
                // Check if the product is null
                if (!$product) {
                    return null; // Or handle it as needed (e.g., skip this entry, log it, etc.)
                }
        
                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'best_delivery_date' => $product->best_delivery_date,
                    'total_reviews' => $product->reviews->count(),
                    'avg_rating' => $product->reviews->count() > 0 ? $product->reviews->avg('star') : null,
                    'left_stock' => $product->left_stock ?? 0,
                    'currency' => $product->currency->title ?? 'USD',
                    'in_wishlist' => in_array($product->id, $wishlistIds),
                    'images' => collect($product->images)->map(function ($image) {
                        if (filter_var($image, FILTER_VALIDATE_URL)) {
                            return $image;
                        }
                        $baseUrl = (strpos($image, 'storage/products/') === 0) ? url('storage/products/') : url('storage/');
                        return $baseUrl . '/' . ltrim($image, '/');
                    })->toArray(),
                    'original_price' => $product->price,
                    'front_sale_price' => $product->price,
                    'best_price' => $product->price,
                ];
            })->filter(), // Filter out null values
        ]);
    }
}
}

