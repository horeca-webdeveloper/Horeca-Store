<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Ecommerce\Models\ProductCategory;
use RvMedia;

class CategoryMenuController extends Controller
{
    /**
     * Retrieve categories with children and their respective data.
     */
    public function getCategoriesWithChildren(Request $request)
    {
        $filterId = $request->get('id');

        // Select only the necessary fields and eager load product count
        $query = ProductCategory::select(['id', 'name', 'slug', 'parent_id', 'image'])
            ->withCount('products');

        if ($filterId) {
            $query->where('id', $filterId)->orWhere('parent_id', $filterId);
        }

        $categories = $query->get();

        // Build the optimized category tree
        $categoriesTree = $this->buildCategoryTree($categories);

        return response()->json($categoriesTree);
    }

    /**
     * Build a hierarchical category tree efficiently.
     */
    private function buildCategoryTree($categories)
    {
        $tree = [];
        $categoryMap = [];

        // Create a lookup table for fast access
        foreach ($categories as $category) {
            $categoryMap[$category->id] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'productCount' => $category->products_count, // Eager-loaded product count
                'image' => RvMedia::getImageUrl($category->image), // Full image URL
                'children' => [],
            ];
        }

        // Build the tree using the lookup table
        foreach ($categoryMap as &$category) {
            if ($category['parent_id'] && isset($categoryMap[$category['parent_id']])) {
                $categoryMap[$category['parent_id']]['children'][] = &$category;
            } else {
                $tree[] = &$category;
            }
        }

        return $tree;
    }
}

// namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
// use Botble\Ecommerce\Models\ProductCategory;
// use RvMedia;

// class CategoryMenuController extends Controller
// {
//     /**
//      * Retrieve categories with children and their respective data.
//      */
//     public function getCategoriesWithChildren(Request $request)
//     {
//         $filterId = $request->get('id'); // Optional ID filter
//         $categories = $filterId
//             ? ProductCategory::where('id', $filterId)
//                 ->orWhere('parent_id', $filterId)
//                 ->get()
//             : ProductCategory::all();

//         // Build the category tree
//         $categoriesTree = $this->buildCategoryTree($categories);

//         return response()->json($categoriesTree);
//     }

//     /**
//      * Build a hierarchical category treee.
//      */
//     // private function buildCategoryTree($categories, $parentId = 0)
//     // {
//     //     $tree = [];

//     //     foreach ($categories as $category) {
//     //         if ($category->parent_id == $parentId) {
//     //             // Add children recursively
//     //             $children = $this->buildCategoryTree($categories, $category->id);

//     //             $tree[] = [
//     //                 'id' => $category->id,
//     //                 'name' => $category->name,
//     //                 'slug' => $category->slug,
//     //                 'parent_id' => $category->parent_id,
//     //                 'productCount' => $category->productCount,
//     //                 'children' => $children,
//     //             ];
//     //         }
//     //     }

//     //     return $tree;
//     // }

//     private function buildCategoryTree($categories, $parentId = 0)
// {
//     $tree = [];

//     foreach ($categories as $category) {
//         if ($category->parent_id == $parentId) {
//             // Add children recursively
//             $children = $this->buildCategoryTree($categories, $category->id);

//             $tree[] = [
//                 'id' => $category->id,
//                 'name' => $category->name,
//                 'slug' => $category->slug,
//                 'parent_id' => $category->parent_id,
//                 'productCount' => $category->productCount,
//                 'image' => RvMedia::getImageUrl($category->image), // Get full image URL
//                 'children' => $children,
//             ];
//         }
//     }

//     return $tree;
// }
// }