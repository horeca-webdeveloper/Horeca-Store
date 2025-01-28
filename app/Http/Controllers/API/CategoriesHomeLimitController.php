<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Botble\Ecommerce\Models\ProductCategory;

class CategoriesHomeLimitController extends Controller
{
    /**
     * Fetch 14 categories (parents and children) with their details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchCategories(Request $request)
    {
        // Limit to 14 categories
        $limit = 13;

        // Fetch parent categories
        $parentCategories = ProductCategory::where('parent_id', 0)
            ->get(['id', 'name', 'slug', 'parent_id', 'image']); // Select necessary fields

        // Fetch child categories
        $childCategories = ProductCategory::whereIn('parent_id', $parentCategories->pluck('id'))
            ->get(['id', 'name', 'slug', 'parent_id', 'image']); // Select necessary fields

        // Merge parent and child categories
        $allCategories = $parentCategories->merge($childCategories);

        // Limit the combined result to 14 categories
        $limitedCategories = $allCategories->take($limit);

        // Add product count and adjust image URLs
        foreach ($limitedCategories as $category) {
            $category->productCount = $category->products()->count(); // Count related products
            $category->image = $this->getImageUrl($category->image); // Adjust image URL
        }

        // Return categories with their details
        return response()->json($limitedCategories);
    }

    public function fetchAllCategories(Request $request)
{
    // Fetch parent categories
    $parentCategories = ProductCategory::where('parent_id', 0)
        ->get(['id', 'name', 'slug', 'parent_id', 'image']); // Select necessary fields

    // Fetch child categories
    $childCategories = ProductCategory::whereIn('parent_id', $parentCategories->pluck('id'))
        ->get(['id', 'name', 'slug', 'parent_id', 'image']); // Select necessary fields

    // Merge parent and child categories
    $allCategories = $parentCategories->merge($childCategories);

    // Add product count and adjust image URLs
    foreach ($allCategories as $category) {
        $category->productCount = $category->products()->count(); // Count related products
        $category->image = $this->getImageUrl($category->image); // Adjust image URL
    }

    // Return all categories with their details
    return response()->json($allCategories);
}


    /**
     * Get the full URL of the image, whether it's inside storage/categories or storage.
     *
     * @param  string  $imagePath
     * @return string
     */
    private function getImageUrl($imagePath)
    {
        // Check if the image is inside 'categories' or general 'storage'
        if (strpos($imagePath, 'storage/categories') === 0) {
            return asset('storage/' . $imagePath); // If inside storage/categories, use the asset helper
        } elseif (strpos($imagePath, 'storage') === 0) {
            return asset('storage/' . $imagePath); // If inside any storage folder, use the asset helper
        }

        // Return default if not found
        return asset('storage/' . $imagePath); 
    }
}
