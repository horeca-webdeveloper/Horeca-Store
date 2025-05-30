<?php


namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Botble\Ecommerce\Models\ProductAttributes;
use App\Http\Controllers\Controller;

class ProductAttributeController extends Controller
{
    // public function getAttributesByProduct($productId)
    // {
    //     // Fetch the product attributes with the associated attribute names
    //     $productAttributes = ProductAttributes::with('attribute:id,name') // Eager load 'attribute' relation
    //         ->where('product_id', $productId) // Filter by product_id
    //         ->get(['attribute_value', 'attribute_id']); // Select 'attribute_value' and 'attribute_id' columns

    //     // Return the data in JSON format
    //     return response()->json($productAttributes);
    // }
    public function getNutritionFactsByProduct1($productId)
    {
        // Fetch attributes only under the "Nutrition Facts Per Serving Group"
        $productAttributes = ProductAttributes::with(['attribute' => function ($query) {
            $query->whereHas('attributeGroup', function ($q) {
                $q->where('name', 'Nutrition Facts Per Serving Group');
            });
        }])
        ->where('product_id', $productId)
        ->get(['attribute_value', 'attribute_id']);

        // Filter to only include those with valid attribute relation
        $nutritionFacts = $productAttributes->filter(function ($item) {
            return $item->attribute !== null;
        })->values();

        if ($nutritionFacts->isEmpty()) {
            return response()->json([
                'message' => 'Nutrition Facts Per Serving Group not found for this product.'
            ], 200);
        }

        return response()->json($nutritionFacts);
    }
    public function getNutritionFactsByProduct($productId)
{
    // Keyword-based sort order (lowercase)
    $sortKeywords = [
        'serving',
        'calories',
        'total fat',
        'saturated fat',
        'trans fat',
        'cholesterol',
        'sodium',
        'total carbohydrate',
        'dietary fiber',
        'total sugars',
        'added sugars',
        'protein',
        'vitamin d',
        'calcium',
        'iron',
        'potassium'
    ];

    // Fetch product attributes in the group
    $productAttributes = ProductAttributes::with(['attribute' => function ($query) {
        $query->whereHas('attributeGroup', function ($q) {
            $q->where('name', 'Nutrition Facts Per Serving Group');
        })->with('attributeGroup');
    }])
    ->where('product_id', $productId)
    ->get(['attribute_value', 'attribute_id']);

    // Filter out null attributes
    $nutritionFacts = $productAttributes->filter(function ($item) {
        return $item->attribute !== null;
    });

    if ($nutritionFacts->isEmpty()) {
        return response()->json([
            'message' => 'Nutrition Facts Per Serving Group not found for this product.'
        ], 200);
    }

    // Sort dynamically based on keyword order
    $sortedFacts = $nutritionFacts->sortBy(function ($item) use ($sortKeywords) {
        $name = strtolower($item->attribute->name);
        foreach ($sortKeywords as $index => $keyword) {
            if (strpos($name, $keyword) !== false) {
                return $index;
            }
        }
        return count($sortKeywords) + 1; // Unknown attributes go to end
    })->values();

    // Build the final response
    $response = [
        'group_name' => $sortedFacts[0]->attribute->attributeGroup->name ?? 'Nutrition Facts Per Serving Group',
        'attributes' => $sortedFacts->map(function ($item) {
            return [
                'name'  => $item->attribute->name,
                'value' => $item->attribute_value
            ];
        })
    ];

    return response()->json($response);
}

    public function getAttributesByProductWithGroup($productId)
    {
        // Get product attributes with related attribute and attribute group
        $productAttributes = ProductAttributes::with(['attribute.attributeGroup'])
            ->where('product_id', $productId)
            ->get();

        // Grouping logic
        $groupedAttributes = [];

        foreach ($productAttributes as $productAttribute) {
            $attribute = $productAttribute->attribute;
            if (!$attribute) continue;

            $groupName = $attribute->attributeGroup->name ?? 'Other';

            $groupedAttributes[$groupName][] = [
                'name'  => $attribute->name,
                'value' => $productAttribute->attribute_value,
            ];
        }

        // Formatting final response
        $formatted = [];
        foreach ($groupedAttributes as $section => $specs) {
            $formatted[] = [
                'section' => $section,
                'specs' => $specs,
            ];
        }

        return response()->json($formatted);
    }

    // public function getAttributesByProductWithGroup($productId)
    // {
    //         $productAttributes = ProductAttributes::with(['attribute.attributeGroups'])
    //         ->where('product_id', $productId)
    //         ->get();

    //     $groupedAttributes = [];

    //     foreach ($productAttributes as $productAttribute) {
    //         $attribute = $productAttribute->attribute;

    //         if (!$attribute || $attribute->attributeGroups->isEmpty()) {
    //             $groupName = 'Other';
    //         } else {
    //             // If attribute belongs to multiple groups, pick the first
    //             $groupName = $attribute->attributeGroups->first()->name;
    //         }

    //         $groupedAttributes[$groupName][] = [
    //             'name' => $attribute->name,
    //             'value' => $productAttribute->attribute_value,
    //         ];
    //     }

    //     // Final formatting
    //     $formatted = [];
    //     foreach ($groupedAttributes as $section => $specs) {
    //         $formatted[] = [
    //             'section' => $section,
    //             'specs' => $specs,
    //         ];
    //     }

    //     return response()->json($formatted);
    // }
}
