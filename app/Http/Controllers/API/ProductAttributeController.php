<?php


namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Botble\Ecommerce\Models\ProductAttributes;
use App\Http\Controllers\Controller;

class ProductAttributeController extends Controller
{
    public function getAttributesByProduct($productId)
    {
        // Fetch the product attributes with the associated attribute names
        $productAttributes = ProductAttributes::with('attribute:id,name') // Eager load 'attribute' relation
            ->where('product_id', $productId) // Filter by product_id
            ->get(['attribute_value', 'attribute_id']); // Select 'attribute_value' and 'attribute_id' columns

        // Return the data in JSON format
        return response()->json($productAttributes);
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
}
