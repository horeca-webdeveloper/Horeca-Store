<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Botble\Ecommerce\Models\ProductCategory;

class CategoryWithSlugController extends Controller
{
    /**
     * Fetch category by slug with its children and children's children (parent ID included).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function showCategoryBySlug($slug)
    {
        // Fetch the category by slug
        $category = ProductCategory::where('slug', $slug)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Fetch children of this category recursively
        $categoryWithChildren = $this->getCategoryWithChildren($category);

        // Return the category with children and their respective children
        return response()->json($categoryWithChildren);
    }

    /**
     * Recursive function to fetch category and its children recursively.
     *
     * @param  \App\Models\ProductCategory  $category
     * @return \App\Models\ProductCategory
     */
    private function getCategoryWithChildren($category)
    {
        // Get the children of the category
        $children = ProductCategory::where('parent_id', $category->id)->get();

        // Iterate through each child and fetch its children recursively
        foreach ($children as $child) {
            // Prevent the 'children' attribute from causing recursion in JSON
            $child->setRelation('children', $this->getCategoryWithChildren($child));
        }

        // Add the children to the current category
        $category->children = $children;

        // Return the category with its children
        return $category->only(['id', 'name', 'slug', 'parent_id', 'children']);
    }
}