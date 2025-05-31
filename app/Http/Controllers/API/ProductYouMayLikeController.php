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
    public function getProductsYouMayLike(Request $request, $product_id = null)
    {
        $productId = $product_id ?? $request->input('product_id');

        if (!$productId) {
            return response()->json(['success' => false, 'message' => 'Product ID is required'], 400);
        }

        $userId = Auth::guard('api')->id();
        $wishlistProductIds = $userId
            ? DB::table('ec_wish_lists')->where('customer_id', $userId)->pluck('product_id')->toArray()
            : session()->get('guest_wishlist', []);

        $productYouMayLike = DB::table('product_you_may_likes')->where('product_id', $productId)->first();

        if (!$productYouMayLike) {
            $relatedProductRecord = DB::table('product_you_may_like_items')->where('product_id', $productId)->first();
            if ($relatedProductRecord) {
                $productYouMayLike = DB::table('product_you_may_likes')->where('id', $relatedProductRecord->product_you_may_like_id)->first();
            }
        }

        if (!$productYouMayLike) {
            return response()->json(['success' => true, 'message' => 'No related products found', 'data' => []]);
        }

        $relatedProductIds = DB::table('product_you_may_like_items')
            ->where('product_you_may_like_id', $productYouMayLike->id)
            ->orderBy('priority')
            ->pluck('product_id')
            ->filter(fn ($id) => $id != $productId)
            ->values()
            ->toArray();

        if (empty($relatedProductIds)) {
            return response()->json(['success' => true, 'message' => 'No related products found', 'data' => []]);
        }

        $products = Product::with(['reviews:id,product_id,star', 'currency', 'specifications'])
            ->where('status', 'published')
            ->whereIn('id', $relatedProductIds)
            ->get()
            ->sortBy(fn ($product) => array_search($product->id, $relatedProductIds))
            ->values();

        $response = $products->map(function ($product) use ($wishlistProductIds) {
            $images = collect($product->images)->map(fn ($img) => filter_var($img, FILTER_VALIDATE_URL) ? $img : url('storage/' . ltrim($img, '/')));
            $videos = collect(json_decode($product->video_path, true) ?: [])->map(fn ($vid) => filter_var($vid, FILTER_VALIDATE_URL) ? $vid : url('storage/' . ltrim($vid, '/')));

            $avgRating = optional($product->reviews)->avg('star');
            $quantity = $product->quantity ?? 0;
            $unitsSold = $product->units_sold ?? 0;
            $leftStock = $quantity - $unitsSold;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'images' => $images,
                'video_url' => $product->video_url,
                'video_path' => $videos,
                'original_price' => $product->price,
                'front_sale_price' => $product->price,
                'sale_price' => $product->sale_price,
                'price' => $product->price,
                'avg_rating' => $avgRating,
                'total_reviews' => $product->reviews->count(),
                'is_in_wishlist' => in_array($product->id, $wishlistProductIds),
                'left_stock' => $leftStock > 0 ? $leftStock : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Related products retrieved successfully',
            'data' => $response,
            'pagination' => [
                'total' => $response->count(),
                'per_page' => 50,
                'current_page' => 1,
                'last_page' => 1,
                'has_more_pages' => false,
            ]
        ]);
    }
}