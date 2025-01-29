<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Botble\Ecommerce\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductSpecificationApiController extends Controller
{
	public function getProductSpecifications(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'product_id' => 'required|string',
			'specification_type' => 'required'
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'message' => $validator->errors()
			], 400);
		}

		$product = Product::find($request->product_id);
		if (!$product) {
			return response()->json([
				'success' => false,
				'message' => 'Product does not exist.'
			], 400);
		}

		try {
			$specificationType = $request->specification_type;

			$filteredSpecificationNames = $product->latestCategorySpecifications
			->filter(function ($spec) use ($specificationType) {
				return strpos($spec['specification_type'], $specificationType) !== false;
			})
			->pluck('specification_name')
			->all();

			$productSpecifications = $product->specifications->filter(function ($spec) use ($filteredSpecificationNames) {
				return in_array($spec->spec_name, $filteredSpecificationNames);
			});

			return response()->json([
				'success' => true,
				'message' => "Requested product specification list.",
				'data' => $productSpecifications->values()
			], 200);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => "An error occurred: " . $e->getMessage()
			], 500);
		}
	}
}
