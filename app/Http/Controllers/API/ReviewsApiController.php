<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Ecommerce\Models\Review;
use Botble\Ecommerce\Models\Product;

class ReviewsApiController extends Controller
{
    public function getProductReviews(Request $request)
    {
        // Log the request for debugging
        \Log::info($request->all());

        // Get the product ID from the request
        $productId = $request->input('product_id');

        // Calculate the total number of reviews for the product, ignoring filters
        $totalReviews = Review::where('product_id', $productId)->count();

        // Start building the query for filtered reviews
        $query = Review::query();

        // Apply filters for star ratings
        if ($request->has('star')) {
            $star = $request->input('star');
            $query->where('star', $star);
        }

        // Filter by product ID if provided
        if ($request->has('product_id')) {
            // Check if the product exists
            if (!Product::where('id', $productId)->exists()) {
                return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
            }

            $query->where('product_id', $productId);
        }

        // Filter for highest ratings
        if ($request->has('sort') && $request->input('sort') === 'highest') {
            $query->orderBy('star', 'desc');
        }

        // Filter for lowest ratings
        if ($request->has('sort') && $request->input('sort') === 'lowest') {
            $query->orderBy('star', 'asc');
        }

        // Get reviews with pagination after applying filters
        $reviews = $query->paginate($request->input('per_page', 15));

        // Count how many 1, 2, 3, 4, and 5-star reviews for the specific product
        $starCounts = [
            '1_star' => Review::where('star', 1)->where('product_id', $productId)->count(),
            '2_star' => Review::where('star', 2)->where('product_id', $productId)->count(),
            '3_star' => Review::where('star', 3)->where('product_id', $productId)->count(),
            '4_star' => Review::where('star', 4)->where('product_id', $productId)->count(),
            '5_star' => Review::where('star', 5)->where('product_id', $productId)->count(),
        ];

        // Calculate average rating
        $averageRating = Review::where('product_id', $productId)->avg('star');

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews, // Paginated reviews after filters applied
                'total_reviews' => $totalReviews, // Total reviews before any filters
                'star_counts' => $starCounts, // Star breakdown
                'average_rating' => round($averageRating, 2), // Rounded to 2 decimal places
                'product_id' => $productId, // Include product ID in the response
            ],
        ]);
    }
}
