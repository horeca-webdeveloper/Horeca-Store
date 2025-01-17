<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Botble\Ecommerce\Models\ProductCategory;
class CategoriesHomeLimitController extends Controller
{
    /**
     * Fetch 14 categories with their slug, id, parent_id, image, and product count.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchCategories(Request $request)
    {
        // Limit to 14 categories
        $limit = 14;

        // Fetch the first 14 categories (parent categories)
        $categories = ProductCategory::where('parent_id', 0)
            ->take($limit)
            ->get(['id', 'name', 'slug', 'parent_id', 'image']); // Select necessary fields

        // Add product count by counting the related products in the ec_product_category_product table
        foreach ($categories as $category) {
            $category->productCount = $category->products()->count(); // Count related products via the relationship
            
            // Check the image location and generate the correct URL
            $category->image = $this->getImageUrl($category->image);
        }

        // Return categories with their details
        return response()->json($categories);
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