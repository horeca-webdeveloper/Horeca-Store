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
     * @param  \Botble\Ecommerce\Models\ProductCategory  $category
     * @return array
     */
    private function getCategoryWithChildren($category)
    {
        // Get the children of the category
        $children = ProductCategory::where('parent_id', $category->id)->get();

        // Iterate through each child and fetch its children recursively
        foreach ($children as $child) {
            // Add image URL
            $child->image = $this->getImageUrl($child->image);

            // Prevent the 'children' attribute from causing recursion in JSON
            $child->setRelation('children', $this->getCategoryWithChildren($child));
        }

        // Add image URL for the current category
        $category->image = $this->getImageUrl($category->image);

        // Add the children to the current category
        $category->children = $children;

        // Return the category with its children
        return $category->only(['id', 'name', 'slug', 'parent_id', 'image', 'children']);
    }

    /**
     * Resolve the full URL for the category image.
     *
     * @param  string|null  $imagePath
     * @return string
     */
    private function getImageUrl($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        // Return as-is if the image path starts with http
        if (str_starts_with($imagePath, 'http')) {
            return $imagePath;
        }

        $baseUrl = url('/');
        $storagePath = "/storage/";

        // Check if the image is in the storage directory
        if (file_exists(public_path($storagePath . $imagePath))) {
            return $baseUrl . $storagePath . $imagePath;
        }

        // Otherwise, assume it's in the storage/categories directory
        $categoriesPath = $storagePath . "categories/";
        if (file_exists(public_path($categoriesPath . $imagePath))) {
            return $baseUrl . $categoriesPath . $imagePath;
        }

        // Return the default URL if no file is found
        return $baseUrl . $storagePath . $imagePath;
    }
}
