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
}