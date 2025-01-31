<?php

namespace Botble\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Botble\Ecommerce\Models\ProductTypes;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\CategorySpecification;

class CategoryProductTypeController extends BaseController
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		// Get the search query from the request
		$search = $request->input('search');

		// Fetch filtered categories or all categories if no search query
		// $categories = ProductCategory::with(['productTypes', 'specifications'])
		$categories = ProductCategory::with(['specifications'])
		->whereDoesntHave('children')
		->when($search, function ($query, $search) {
			$query->where(function ($q) use ($search) {
				$q->where('id', $search)
				->orWhere('name', 'like', '%' . $search . '%');
			});
		})
		->paginate(20)
		->through(function ($category) {
			return [
				'id' => $category->id,
				'name' => $category->name,
				// 'product_types' => $category->productTypes ? $category->productTypes->pluck('name')->implode(', ') : '',
				'specifications' => $category->specifications ? $category->specifications->pluck('specification_name')->implode(', ') : '',
			];
		});

		// Pass search query back to the view
		return view('plugins/ecommerce::category-product-type.index', compact('categories', 'search'));
	}

	/**
	 * Display the specified resource.
	 */
	public function edit($id)
	{
		// Fetch the category with product types and specifications
		$category = ProductCategory::with(['productTypes', 'specifications'])->findOrFail($id);

		// Fetch all available product types for the multi-select
		// $productTypes = ProductTypes::all(['id', 'name']);
		$specificationTypes = ['At a Glance', 'Comparison', 'Filters'];
		$specificationNames = CategorySpecification::pluck('specification_name')->uniqueStrict()->toArray();
		// Pass the data to the edit view
		// return view('plugins/ecommerce::category-product-type.edit', compact('category', 'productTypes'));
		return view('plugins/ecommerce::category-product-type.edit', compact('category', 'specificationTypes', 'specificationNames'));
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, $id)
	{
		$category = ProductCategory::findOrFail($id);

		// dd($request->all());

		// Update product types
		// $category->productTypes()->sync($request->input('product_types', []));

		// Update specifications
		$category->specifications()->delete();
		foreach ($request->input('specifications', []) as $specification) {
			if (!empty($specification['name'])) {
				$exists = $category->specifications()->where('specification_name', $specification['name'])->exists();
				if (!$exists) {
					$category->specifications()->create([
						'specification_type' => isset($specification['specification_type']) ? implode(',', $specification['specification_type']) : '',
						'specification_name' => $specification['name'],
						'specification_values' => implode('|', array_unique(array_filter($specification['vals'], fn($val) => !is_null($val))))
					]);
				}
			}
		}

		// Get `search` and `page` query parameters
		$search = $request->input('search');
		$page = $request->input('page');

		// Redirect back to the index with the search and page parameters
		return redirect()->route('categoryFilter.index', ['search' => $search, 'page' => $page])
		->with('success', 'Category updated successfully.');
	}
}