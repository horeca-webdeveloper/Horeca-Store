<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Botble\Ecommerce\Models\Product;

class ProductYouMayLikeController extends Controller
{
    /**
     * Get products you may like based on a given product.
     *
     * @param Request $request
     * @param int|null $product_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductsYouMayLike(Request $request, $product_id = null)
    {
        try {
            // Get product ID from route param or request input
            $productId = $product_id ?? $request->input('product_id');

            if (!$productId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product ID is required',
                ], 400);
            }

            $userId = Auth::id();
            $isUserLoggedIn = $userId !== null;

            Log::info('Fetching recommendations for product:', ['product_id' => $productId, 'user_id' => $userId]);

            // Get wishlist product IDs (for logged in user or guest)
            $wishlistProductIds = $isUserLoggedIn
                ? DB::table('ec_wish_lists')->where('customer_id', $userId)->pluck('product_id')->map(fn($id) => (int) $id)->toArray()
                : session()->get('guest_wishlist', []);

            // Step 1: Find the main "product_you_may_likes" record for this product
            $productYouMayLike = DB::table('product_you_may_likes')
                ->where('product_id', $productId)
                ->first();

            Log::info('ProductYouMayLike lookup:', [
                'product_id' => $productId,
                'found' => $productYouMayLike ? 'yes' : 'no',
                'record_id' => $productYouMayLike->id ?? null,
            ]);

            if (!$productYouMayLike) {
                return response()->json([
                    'success' => true,
                    'message' => 'No related products found for this product',
                    'data' => [],
                    'pagination' => $this->emptyPagination(),
                ]);
            }

            // Step 2: Fetch all recommended products linked to this "product_you_may_like" record by product_you_may_like_id
           // Step 2 (Updated): Get only product IDs from product_you_may_like_items where the product exists in ec_products
            $relatedProductIds = DB::table('product_you_may_like_items as pyml')
            ->distinct()
            ->where('pyml.product_id', $productId)
            ->whereIn('pyml.product_you_may_like_id', function ($query) {
                $query->select('id')->from('ec_products');
            })
            ->pluck('pyml.product_you_may_like_id')
            ->toArray();


            Log::info('ProductYouMayLikeItems:', [
                'product_you_may_like_id' => $productYouMayLike->id,
                'count' => count($relatedProductIds),
                'product_ids' => $relatedProductIds,
            ]);

            if (empty($relatedProductIds)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No recommended products configured',
                    'data' => [],
                    'pagination' => $this->emptyPagination(),
                ]);
            }

            // Step 3: Get published products matching recommended IDs
            $productsQuery = Product::with(['categories', 'brand', 'tags', 'producttypes'])
                ->where('status', 'published')
                ->whereIn('id', $relatedProductIds);

            $products = $productsQuery->get();

            Log::info('Products query result:', [
                'expected_ids' => $relatedProductIds,
                'found_count' => $products->count(),
                'found_ids' => $products->pluck('id')->toArray(),
            ]);

            if ($products->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No published products found in recommendations',
                    'data' => [],
                    'pagination' => $this->emptyPagination(),
                ]);
            }

            // Sort products to preserve priority order
            $products = $products->sortBy(fn($product) => array_search($product->id, $relatedProductIds));

            // Pagination logic
            $perPage = 50;
            $page = max(1, (int) $request->input('page', 1));
            $total = $products->count();
            $offset = ($page - 1) * $perPage;
            $paginatedProducts = $products->slice($offset, $perPage);

            // Load additional relationships for paginated products
            $productIds = $paginatedProducts->pluck('id')->toArray();
            $productsWithRelations = Product::whereIn('id', $productIds)
                ->with(['reviews:id,product_id,star', 'currency', 'specifications'])
                ->get()
                ->keyBy('id');

            // Prepare pagination metadata
            $pagination = $this->buildPagination($page, $perPage, $total);

            // Transform products for response
            $transformedProducts = $paginatedProducts->map(function ($product) use ($wishlistProductIds, $productsWithRelations) {
                $productWithRelations = $productsWithRelations->get($product->id) ?? $product;

                // Decode benefit features safely
                // $benefitFeatures = [];
                // if (!empty($product->benefit_features)) {
                //     $decoded = json_decode($product->benefit_features, true);
                //     if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                //         $benefitFeatures = array_map(fn($b) => [
                //             'benefit' => $b['benefit'] ?? null,
                //             'feature' => $b['feature'] ?? null,
                //         ], $decoded);
                //     }
                // }

                // Normalize images and videos URLs
                $images = $this->normalizeMediaUrls($product->images);
                $videos = $this->normalizeMediaUrls($product->video_path);

                $totalReviews = $productWithRelations->reviews ? $productWithRelations->reviews->count() : 0;
                $avgRating = $totalReviews > 0 ? $productWithRelations->reviews->avg('star') : null;

                $quantity = $product->quantity ?? 0;
                $unitsSold = $product->units_sold ?? 0;
                $leftStock = $quantity - $unitsSold;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'images' => $images,
                    'video_url' => $product->video_url,
                    'video_path' => $videos,
                    'sku' => $product->sku,
                    'original_price' => $product->price,
                    'front_sale_price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'price' => $product->price,
                    'start_date' => $product->start_date,
                    'end_date' => $product->end_date,
                    'warranty_information' => $product->warranty_information,
                    'currency' => $productWithRelations->currency?->title,
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
                    'in_wishlist' => in_array($product->id, $wishlistProductIds)
                ];
            });

            Log::info('Returning products:', [
                'total' => $total,
                'page' => $page,
                'count' => $transformedProducts->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $transformedProducts->values(),
                'pagination' => $pagination,
                'message' => 'Products you may like retrieved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getProductsYouMayLike: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching products you may like',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return empty pagination structure.
     */
    protected function emptyPagination(): array
    {
        return [
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
        ];
    }

    /**
     * Build pagination metadata array.
     */
    protected function buildPagination(int $currentPage, int $perPage, int $total): array
    {
        $lastPage = (int) ceil($total / $perPage);
        $startPage = max($currentPage - 2, 1);
        $endPage = min($startPage + 4, $lastPage);

        if ($endPage - $startPage < 4) {
            $startPage = max($endPage - 4, 1);
        }

        return [
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
    }

    /**
     * Normalize image/video URLs (accepts JSON string or array).
     */
    protected function normalizeMediaUrls($media)
    {
        if (empty($media)) {
            return [];
        }

        if (is_string($media)) {
            $decoded = json_decode($media, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return is_array($decoded) ? $decoded : [];
            }
            return [];
        }

        if (is_array($media)) {
            return $media;
        }

        return [];
    }

    public function getProductsYouMayLikeGuest(Request $request, $product_id = null)
    {
        try {
            $productId = $product_id ?? $request->input('product_id');
    
            if (!$productId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product ID is required',
                ], 400);
            }
    
            Log::info('Fetching recommendations for product:', ['product_id' => $productId]);
    
            // Step 1: Find the main "product_you_may_likes" record for this product
            $productYouMayLike = DB::table('product_you_may_likes')
                ->where('product_id', $productId)
                ->first();
    
            Log::info('ProductYouMayLike lookup:', [
                'product_id' => $productId,
                'found' => $productYouMayLike ? 'yes' : 'no',
                'record_id' => $productYouMayLike->id ?? null,
            ]);
    
            if (!$productYouMayLike) {
                return response()->json([
                    'success' => true,
                    'message' => 'No related products found for this product',
                    'data' => [],
                    'pagination' => $this->emptyPagination(),
                ]);
            }
    
            // Step 2: Get only product IDs from product_you_may_like_items where the product exists in ec_products
            $relatedProductIds = DB::table('product_you_may_like_items as pyml')
                ->distinct()
                ->where('pyml.product_id', $productId)
                ->whereIn('pyml.product_you_may_like_id', function ($query) {
                    $query->select('id')->from('ec_products');
                })
                ->pluck('pyml.product_you_may_like_id')
                ->toArray();
    
            Log::info('ProductYouMayLikeItems:', [
                'product_you_may_like_id' => $productYouMayLike->id,
                'count' => count($relatedProductIds),
                'product_ids' => $relatedProductIds,
            ]);
    
            if (empty($relatedProductIds)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No recommended products configured',
                    'data' => [],
                    'pagination' => $this->emptyPagination(),
                ]);
            }
    
            $productsQuery = Product::with(['categories', 'brand', 'tags', 'producttypes'])
                ->where('status', 'published')
                ->whereIn('id', $relatedProductIds);
    
            $products = $productsQuery->get();
    
            Log::info('Products query result:', [
                'expected_ids' => $relatedProductIds,
                'found_count' => $products->count(),
                'found_ids' => $products->pluck('id')->toArray(),
            ]);
    
            if ($products->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No published products found in recommendations',
                    'data' => [],
                    'pagination' => $this->emptyPagination(),
                ]);
            }
    
            $products = $products->sortBy(fn($product) => array_search($product->id, $relatedProductIds));
    
            $perPage = 50;
            $page = max(1, (int) $request->input('page', 1));
            $total = $products->count();
            $offset = ($page - 1) * $perPage;
            $paginatedProducts = $products->slice($offset, $perPage);
    
            $productIds = $paginatedProducts->pluck('id')->toArray();
            $productsWithRelations = Product::whereIn('id', $productIds)
                ->with(['reviews:id,product_id,star', 'currency', 'specifications'])
                ->get()
                ->keyBy('id');
    
            $pagination = $this->buildPagination($page, $perPage, $total);
    
            $transformedProducts = $paginatedProducts->map(function ($product) use ($productsWithRelations) {
                $productWithRelations = $productsWithRelations->get($product->id) ?? $product;
    
                $images = $this->normalizeMediaUrls($product->images);
                $videos = $this->normalizeMediaUrls($product->video_path);
    
                $totalReviews = $productWithRelations->reviews ? $productWithRelations->reviews->count() : 0;
                $avgRating = $totalReviews > 0 ? $productWithRelations->reviews->avg('star') : null;
    
                $quantity = $product->quantity ?? 0;
                $unitsSold = $product->units_sold ?? 0;
                $leftStock = $quantity - $unitsSold;
    
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'images' => $images,
                    'video_url' => $product->video_url,
                    'video_path' => $videos,
                    'sku' => $product->sku,
                    'original_price' => $product->price,
                    'front_sale_price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'price' => $product->price,
                    'start_date' => $product->start_date,
                    'end_date' => $product->end_date,
                    'warranty_information' => $product->warranty_information,
                    'currency' => $productWithRelations->currency?->title,
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
                ];
            });
    
            Log::info('Returning products:', [
                'total' => $total,
                'page' => $page,
                'count' => $transformedProducts->count(),
            ]);
    
            return response()->json([
                'success' => true,
                'data' => $transformedProducts->values(),
                'pagination' => $pagination,
                'message' => 'Products you may like retrieved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getProductsYouMayLike: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
    
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching products you may like',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
}
