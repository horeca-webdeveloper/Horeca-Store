<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductYouMayLikeItem;
use Botble\Ecommerce\Models\ProductYouMayLike;


class ProductYouMayLikeController extends Controller
{
    /**
     * Get products you may like based on a given product
     *
     * @param Request $request
     * @param int|null $product_id (route parameter)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductsYouMayLike(Request $request, $product_id = null)
    {
        try {
            // Get the product_id from route parameter or request input
            $productId = $product_id ?? $request->input('product_id');
            
            if (!$productId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product ID is required'
                ], 400);
            }

            // Keep existing user and wishlist logic
            $userId = Auth::id();
            $isUserLoggedIn = $userId !== null;

            Log::info('Fetching recommendations for product:', ['product_id' => $productId, 'user_id' => $userId]);

            $wishlistProductIds = [];
            if ($isUserLoggedIn) {
                $wishlistProductIds = DB::table('ec_wish_lists')
                    ->where('customer_id', $userId)
                    ->pluck('product_id')
                    ->map(function($id) {
                        return (int) $id;
                    })
                    ->toArray();
            } else {
                $wishlistProductIds = session()->get('guest_wishlist', []);
            }

            // Step 1: Find the product_you_may_like record for this product
            $productYouMayLike = DB::table('product_you_may_likes')
                ->where('product_id', $productId)
                ->first();

            Log::info('ProductYouMayLike lookup result:', [
                'product_id' => $productId,
                'found' => $productYouMayLike ? 'yes' : 'no',
                'record_id' => $productYouMayLike->id ?? null
            ]);

            if (!$productYouMayLike) {
                return response()->json([
                    'success' => true,
                    'message' => 'No related products found for this product',
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 50,
                        'total' => 0,
                        'has_more_pages' => false,
                        'visible_pages' => [1],
                        'has_previous' => false,
                        'has_next' => false,
                        'previous_page' => 0,
                        'next_page' => 2,
                    ]
                ]);
            }

            // Step 2: Get all the recommended product IDs for this product_you_may_like record
            // The product_you_may_like_id in items table should match the ID from product_you_may_likes table
            $recommendedProducts = DB::table('product_you_may_like_items')
                ->where('product_you_may_like_id', $productYouMayLike->id)
                ->orderBy('priority', 'asc')
                ->get(['product_id', 'priority']);

            $relatedProductIds = $recommendedProducts->pluck('product_id')->toArray();

            Log::info('ProductYouMayLikeItems lookup result:', [
                'product_you_may_like_id' => $productYouMayLike->id,
                'found_items_count' => count($relatedProductIds),
                'product_ids' => $relatedProductIds,
                'priorities' => $recommendedProducts->pluck('priority')->toArray()
            ]);

            if (empty($relatedProductIds)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No recommended products configured',
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 50,
                        'total' => 0,
                        'has_more_pages' => false,
                        'visible_pages' => [1],
                        'has_previous' => false,
                        'has_next' => false,
                        'previous_page' => 0,
                        'next_page' => 2,
                    ]
                ]);
            }

            // Step 3: Get the actual products from the products table
            $productsQuery = Product::with(['categories', 'brand', 'tags', 'producttypes'])
                ->where('status', 'published')
                ->whereIn('id', $relatedProductIds);

            $products = $productsQuery->get();

            Log::info('Products query result:', [
                'searched_ids' => $relatedProductIds,
                'found_products_count' => $products->count(),
                'found_product_ids' => $products->pluck('id')->toArray(),
                'missing_ids' => array_diff($relatedProductIds, $products->pluck('id')->toArray())
            ]);

            // Sort products by priority order
            $products = $products->sortBy(function($product) use ($relatedProductIds) {
                return array_search($product->id, $relatedProductIds);
            });

            if ($products->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No published products found in recommendations',
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 50,
                        'total' => 0,
                        'has_more_pages' => false,
                        'visible_pages' => [1],
                        'has_previous' => false,
                        'has_next' => false,
                        'previous_page' => 0,
                        'next_page' => 2,
                    ]
                ]);
            }

            // Paginate the results
            $perPage = 50;
            $page = $request->input('page', 1);
            $total = $products->count();
            $offset = ($page - 1) * $perPage;
            $paginatedProducts = $products->slice($offset, $perPage);

            // Load additional relationships for paginated products
            $productIds = $paginatedProducts->pluck('id')->toArray();
            $productsWithRelations = Product::whereIn('id', $productIds)
                ->with([
                    'reviews' => function($query) {
                        $query->select('id', 'product_id', 'star');
                    },
                    'currency',
                    'specifications'
                ])
                ->get()
                ->keyBy('id');

            // Calculate pagination details
            $lastPage = ceil($total / $perPage);
            $currentPage = $page;
            $startPage = max($currentPage - 2, 1);
            $endPage = min($startPage + 4, $lastPage);

            if ($endPage - $startPage < 4) {
                $startPage = max($endPage - 4, 1);
            }

            $pagination = [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'has_more_pages' => $currentPage < $lastPage,
                'visible_pages' => range($startPage, $endPage),
                'has_previous' => $currentPage > 1,
                'has_next' => $currentPage < $lastPage,
                'previous_page' => $currentPage - 1,
                'next_page' => $currentPage + 1,
            ];

            // Transform products to match the required response format
            $transformedProducts = $paginatedProducts->map(function ($product) use ($wishlistProductIds, $productsWithRelations) {
                // Get the product with relations
                $productWithRelations = $productsWithRelations->get($product->id) ?? $product;

                // Handle benefit features
                if (!empty($product->benefit_features)) {
                    $decodedBenefits = json_decode($product->benefit_features, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedBenefits)) {
                        $product->benefit_features = array_map(function ($benefit) {
                            return [
                                'benefit' => $benefit['benefit'] ?? null,
                                'feature' => $benefit['feature'] ?? null,
                            ];
                        }, $decodedBenefits);
                    } else {
                        $product->benefit_features = [];
                    }
                }

                // Handle images
                $images = $product->images;
                if (is_string($images)) {
                    $images = json_decode($images, true) ?? [];
                }
                $product->images = collect($images)->map(function ($image) {
                    return filter_var($image, FILTER_VALIDATE_URL) ? $image : url('storage/' . ltrim($image, '/'));
                });

                // Handle video paths
                $videoPaths = $product->video_path;
                if (is_string($videoPaths)) {
                    $videoPaths = json_decode($videoPaths, true) ?? [];
                }
                $product->video_path = collect($videoPaths)->map(function ($video) {
                    if (filter_var($video, FILTER_VALIDATE_URL)) {
                        return $video;
                    }
                    return url('storage/' . ltrim($video, '/'));
                });

                $totalReviews = $productWithRelations->reviews ? $productWithRelations->reviews->count() : 0;
                $avgRating = $totalReviews > 0 ? $productWithRelations->reviews->avg('star') : null;
                $quantity = $product->quantity ?? 0;
                $unitsSold = $product->units_sold ?? 0;
                $leftStock = $quantity - $unitsSold;

                // Prepare the custom response structure
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'images' => $product->images,
                    'video_url' => $product->video_url,
                    'video_path' => $product->video_path,
                    'sku' => $product->sku,
                    'original_price' => $product->price,
                    'front_sale_price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'price' => $product->price,
                    'start_date' => $product->start_date,
                    'end_date' => $product->end_date,
                    'warranty_information' => $product->warranty_information,
                    'currency' => $productWithRelations->currency ? $productWithRelations->currency->title : null,
                    'total_reviews' => $totalReviews,
                    'avg_rating' => $avgRating,
                    'best_price' => $product->sale_price ?? $product->price,
                    'best_delivery_date' => null,
                    'leftStock' => $leftStock,
                    'currency_title' => $productWithRelations->currency
                        ? ($productWithRelations->currency->is_prefix_symbol
                            ? $productWithRelations->currency->title
                            : ($product->price . ' ' . $productWithRelations->currency->title))
                        : $product->price,
                    'in_wishlist' => in_array($product->id, $wishlistProductIds),
                ];
            });

            Log::info('Successfully returning products:', [
                'total_found' => $total,
                'page' => $currentPage,
                'per_page' => $perPage,
                'returning_count' => $transformedProducts->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $transformedProducts->values(),
                'pagination' => $pagination,
                'message' => 'Products you may like retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getProductsYouMayLike: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching products you may like',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}