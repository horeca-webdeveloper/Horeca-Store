<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Botble\Ecommerce\Models\ProductCategory;

class CategoryMenuController extends Controller
{
    /**
     * Retrieve categories with children and their respective data.
     */
    public function getCategoriesWithChildren(Request $request)
    {
        $filterId = $request->get('id'); // Optional ID filter
        $categories = $filterId
            ? ProductCategory::where('id', $filterId)
                ->orWhere('parent_id', $filterId)
                ->get()
            : ProductCategory::all();

        // Build the category tree
        $categoriesTree = $this->buildCategoryTree($categories);

        return response()->json($categoriesTree);
    }

    /**
     * Build a hierarchical category tree.
     */
    private function buildCategoryTree($categories, $parentId = 0)
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                // Add children recursively
                $children = $this->buildCategoryTree($categories, $category->id);

                $tree[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parent_id' => $category->parent_id,
                    'productCount' => $category->productCount,
                    'children' => $children,
                ];
            }
        }

        return $tree;
    }
}