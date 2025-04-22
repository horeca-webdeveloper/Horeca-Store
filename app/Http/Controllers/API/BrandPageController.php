<?php
namespace App\Http\Controllers\API;

use Botble\Ecommerce\Models\Brand;
use Botble\Ecommerce\Models\BrandTemp1;
use Botble\Ecommerce\Models\BrandTemp2;
use Botble\Ecommerce\Models\BrandTemp3;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class BrandPageController extends Controller
{
    public function show($id): JsonResponse
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json(['error' => 'Brand not found'], 404);
        }

        // Try finding matching template data
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

        return response()->json([
            'brand' => $brand,
            'template_type' => $templateType,
            'template_data' => $templateData,
        ]);
    }
}