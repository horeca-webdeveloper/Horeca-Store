<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Models\ProductCategory;

class DiscountsApiController extends Controller
{
    public function getDiscountsForProduct(Request $request)
    {
        // Log the request for debugging
        \Log::info($request->all());

        // Validate that a product_id is provided
        $request->validate([
            'product_id' => 'required|integer|exists:ec_products,id',
        ]);

        // Get the product ID from the request
        $productId = $request->input('product_id');

        // Fetch product-specific discounts
        $productDiscounts = Discount::whereHas('products', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })
        ->active() // Only get active discounts
        ->available() // Only get available discounts
        ->get();

        // Fetch categories the product belongs to
        $productCategories = ProductCategory::whereHas('products', function ($query) use ($productId) {
            $query->where('id', $productId);
        })->pluck('id');

        // Fetch category-level discounts using the productCategories relationship
        $categoryDiscounts = Discount::whereHas('productCategories', function ($query) use ($productCategories) {
            $query->whereIn('product_category_id', $productCategories);
        })
        ->active() // Only get active discounts
        ->available() // Only get available discounts
        ->get();

        // Merge or prioritize discounts
        $discounts = $productDiscounts->isNotEmpty() ? $productDiscounts : $categoryDiscounts;

        // If both product-specific and category discounts exist, prioritize product-specific discounts
        if ($productDiscounts->isNotEmpty() && $categoryDiscounts->isNotEmpty()) {
            $discounts = $productDiscounts; // Prioritize product-specific discounts
        }

        // Check if discounts are found
        if ($discounts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No discounts available for this product or its categories.',
            ], 404);
        }

        // Return the list of discounts
        return response()->json([
            'success' => true,
            'data' => $discounts,
        ]);
    }
}
