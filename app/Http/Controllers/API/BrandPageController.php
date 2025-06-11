<?php
namespace App\Http\Controllers\API;

use Botble\Ecommerce\Models\Brand;
use Botble\Ecommerce\Models\BrandTemp1;
use Botble\Ecommerce\Models\BrandTemp2;
use Botble\Ecommerce\Models\BrandTemp3;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Botble\Ecommerce\Models\Review;
use Botble\Ecommerce\Models\Product;
use Illuminate\Support\Facades\DB;

// class BrandPageController extends Controller
// {
//     public function show($id): JsonResponse
//     {
//         $brand = Brand::find($id);

//         if (!$brand) {
//             return response()->json(['error' => 'Brand not found'], 404);
//         }

//         // Try finding matching template data
//         $templateData = BrandTemp1::where('brand_id', $id)->first();
//         $templateType = 'temp1';

//         if (!$templateData) {
//             $templateData = BrandTemp2::where('brand_id', $id)->first();
//             $templateType = 'temp2';
//         }

//         if (!$templateData) {
//             $templateData = BrandTemp3::where('brand_id', $id)->first();
//             $templateType = 'temp3';
//         }

//         if (!$templateData) {
//             return response()->json(['error' => 'No template data found for this brand'], 404);
//         }

//      // After retrieving the templateData

//         // Get all unique category IDs from the templateData
//         $categoryIds = [];
//         if (!empty($templateData->category_id)) {
//             foreach ($templateData->category_id as $categoryItem) {
//                 if (isset($categoryItem['category_id'])) {
//                     $categoryIds[] = $categoryItem['category_id'];
//                 }
//             }
//         }
//         $categoryIds = array_unique($categoryIds);

//         // Fetch category details from categories table
//         $categories = [];
//         if (!empty($categoryIds)) {
//             $categories = \DB::table('categories')
//                 ->whereIn('id', $categoryIds)
//                 ->get();
//         }

//         // Create a lookup array for easy access to category data
//         $categoryData = [];
//         foreach ($categories as $category) {
//             $categoryData[$category->id] = $category;
//         }

//         // Create a new array with the enhanced category data
//         $enhancedCategoryData = [];
//         if (!empty($templateData->category_id)) {
//             foreach ($templateData->category_id as $categoryItem) {
//                 $catId = $categoryItem['category_id'];
//                 $newItem = $categoryItem; // Copy the original item

//                 if (isset($categoryData[$catId])) {
//                     // Add category details to the copied item
//                     $newItem['category_details'] = $categoryData[$catId];
//                 }

//                 $enhancedCategoryData[] = $newItem;
//             }
//         }

//         // Convert template data to array for modification
//         $templateDataArray = $templateData->toArray();
//         $templateDataArray['category_id'] = $enhancedCategoryData;

//         // Return the response with the modified data
//         return response()->json([
//             'brand' => $brand,
//             'template_type' => $templateType,
//             'template_data' => $templateDataArray,
//         ]);
//     }
// }
class BrandPageController extends Controller
{
    public function show($id): JsonResponse
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json(['error' => 'Brand not found'], 404);
        }

        // Get all products for this brand
        $productIds = Product::where('brand_id', $id)->pluck('id');

        // Calculate average rating and review count
        $reviewStats = DB::table('ec_reviews')
            ->selectRaw('AVG(star) as average_rating, COUNT(*) as review_count')
            ->whereIn('product_id', $productIds)
            ->where('status', 'published') // only include published reviews
            ->first();

        $averageRating = round($reviewStats->average_rating, 1) ?? 0;
        $reviewCount = $reviewStats->review_count ?? 0;

        // Load brand template data
        $templateData = BrandTemp1::where('brand_id', $id)->first();
        $templateType = 'temp1';

        if (!$templateData) {
            $templateData = BrandTemp2::where('brand_id', $id)->first();
            $templateType = 'temp2';
        }

        if (!$templateData) {
            $templateData = BrandTemp3::where('brand_id', $id)->first();
            $templateType = 'temp3';
        }

        if (!$templateData) {
            return response()->json(['error' => 'No template data found for this brand'], 404);
        }

        // Process categories
        $categoryIds = [];
        if (!empty($templateData->category_id)) {
            foreach ($templateData->category_id as $categoryItem) {
                if (isset($categoryItem['category_id'])) {
                    $categoryIds[] = $categoryItem['category_id'];
                }
            }
        }

        $categoryIds = array_unique($categoryIds);
        $categories = [];

        if (!empty($categoryIds)) {
            $categories = DB::table('categories')
                ->whereIn('id', $categoryIds)
                ->get();
        }

        $categoryData = [];
        foreach ($categories as $category) {
            $categoryData[$category->id] = $category;
        }

        $enhancedCategoryData = [];
        if (!empty($templateData->category_id)) {
            foreach ($templateData->category_id as $categoryItem) {
                $catId = $categoryItem['category_id'];
                $newItem = $categoryItem;

                if (isset($categoryData[$catId])) {
                    $newItem['category_details'] = $categoryData[$catId];
                }

                $enhancedCategoryData[] = $newItem;
            }
        }

        // Convert template data to array and inject enhanced category info
        $templateDataArray = $templateData->toArray();
        $templateDataArray['category_id'] = $enhancedCategoryData;

        return response()->json([
            'brand' => $brand,
            'template_type' => $templateType,
            'template_data' => $templateDataArray,
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
        ]);
    }
}