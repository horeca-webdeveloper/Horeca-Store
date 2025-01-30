<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\Specification;

class CategoryController extends Controller
{
	public function index(Request $request)
	{
		$filterId = $request->get('id'); // Optional ID filter
		$limit = $request->get('limit', 12); // Default limit to 12

		if ($filterId) {
			// Fetch the specific category and its children (parent included)
			$categories = ProductCategory::where('id', $filterId)
			->orWhere('parent_id', $filterId)
			->get();
		} else {
			// Fetch all categories if no ID is provided
			$categories = ProductCategory::all();
		}

		// Transform categories into a parent-child structure
		$categoriesTree = $this->buildTree($categories, $filterId, $limit);

		// Add full URLs for images (both parent and child categories)
		foreach ($categoriesTree as $category) {
			$category->image = $this->getImageUrl($category->image); // Modify image for parent category

			// Recursively modify images for children and children's children
			$this->addImageUrlsRecursively($category);
		}

		return response()->json($categoriesTree);
	}

	public function categoryslug(Request $request, $slug)
	{
		$limit = $request->get('limit', 12); // Default limit to 12

		// Fetch the specific category by slug and its children (parent included)
		$parentCategory = ProductCategory::where('slug', $slug)->first();

		if (!$parentCategory) {
			return response()->json(['message' => 'Category not found'], 404);
		}

		$categories = ProductCategory::where('id', $parentCategory->id)
		->orWhere('parent_id', $parentCategory->id)
		->get();

		// Transform categories into a parent-child structure
		$categoriesTree = $this->buildTree($categories, null, $limit);

		// Add full URLs for images (both parent and child categories)
		foreach ($categoriesTree as $category) {
			$category->image = $this->getImageUrl($category->image); // Modify image for parent category

			// Recursively modify images for children and children's children
			$this->addImageUrlsRecursively($category);
		}

		return response()->json($categoriesTree);
	}

	// Recursive function to modify images for children and all sub-level categories
	private function addImageUrlsRecursively($category)
	{
		// If the category has children, modify their images as well
		if (isset($category->children) && !empty($category->children)) {
			foreach ($category->children as $childCategory) {
				$childCategory->image = $this->getImageUrl($childCategory->image); // Modify image for child category
				// Recursively handle children of children (grandchildren, etc.)
				$this->addImageUrlsRecursively($childCategory);
			}
		}
	}

	private function getImageUrl($imagePath)
	{
		if (!$imagePath) {
			return null; // Return null if there's no image path
		}

		// Check if the image exists in the 'products' directory inside storage
		$productsPath = public_path("storage/products/{$imagePath}");
		if (file_exists($productsPath)) {
			return url("storage/products/{$imagePath}");
		}

		// Check if the image exists in the general 'storage' directory inside storage
		$generalStoragePath = public_path("storage/{$imagePath}");
		if (file_exists($generalStoragePath)) {
			return url("storage/{$imagePath}");
		}

		return null; // Return null if the image doesn't exist
	}

	private function buildTree($categories, $parentId = 0, $limit = 12)
	{
		$branch = [];
		$count = 0;

		foreach ($categories as $category) {
			if ($category->parent_id == $parentId) {
				// Count products for the category
				$category->productCount = $category->products()->count();

				// Recursively build children
				$children = $this->buildTree($categories, $category->id, $limit);

				if ($children) {
					$category->children = array_slice($children, 0, $limit);
				} else {
					$category->children = [];
				}

				$branch[] = $category;

				$count++;
				if ($count >= $limit) {
					break;
				}
			}
		}

		return $branch;
	}

	public function show($id)
	{
		$category = ProductCategory::findOrFail($id);
		$category->slug = $category->slug;

		return response()->json([
			'category' => $category,
		]);
	}

	public function store(Request $request)
	{
		$validated = $request->validate([
			'name' => 'required|string|max:255',
			'parent_id' => 'nullable|exists:ec_product_categories,id',
			'description' => 'nullable|string',
			'status' => 'required|boolean',
			'image' => 'nullable|string',
			'is_featured' => 'required|boolean',
			'icon' => 'nullable|string',
			'icon_image' => 'nullable|string',
			'order' => 'nullable|integer',
		]);

		$category = ProductCategory::create($validated);
		return response()->json($category, 201);
	}

	public function update(Request $request, $id)
	{
		$validated = $request->validate([
			'name' => 'required|string|max:255',
			'parent_id' => 'nullable|exists:ec_product_categories,id',
			'description' => 'nullable|string',
			'status' => 'required|boolean',
			'image' => 'nullable|string',
			'is_featured' => 'required|boolean',
			'icon' => 'nullable|string',
			'icon_image' => 'nullable|string',
			'order' => 'nullable|integer',
		]);

		$category = ProductCategory::findOrFail($id);
		$category->update($validated);
		return response()->json($category);
	}

	public function destroy($id)
	{
		$category = ProductCategory::findOrFail($id);
		$category->delete();
		return response()->json(['message' => 'Category deleted successfully']);
	}


	public function getProductsByCategory($categoryId)
	{
		$category = ProductCategory::find($categoryId);

		if (!$category) {
			return response()->json(['message' => 'Category not found'], 404);
		}

		// Update category image URL to include the full path
		$category->image = $this->getCategoryImageUrl($category->image); // Convert the image name to the full URL

		$perPage = request()->get('per_page', 10);
		$perPage = is_numeric($perPage) && $perPage > 0 ? (int)$perPage : 10;

		$products = $category->products()->with(['categories', 'brand', 'tags', 'producttypes'])->paginate($perPage);

		$productTypes = $products->getCollection()->flatMap(function ($product) {
			return $product->producttypes;
		})->unique('id');

		$products->getCollection()->transform(function ($product) {
			$totalReviews = $product->reviews->count();
			$avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;

			$product->total_reviews = $totalReviews;
			$product->avg_rating = $avgRating;

			if ($product->currency) {
				$product->currency_title = $product->currency->is_prefix_symbol
				? $product->currency->title . ' '
				: $product->price . ' ' . $product->currency->title;
			} else {
				$product->currency_title = $product->price;
			}

			// Update product images URLs
			$product->images = collect($product->images)->map(function ($image) {
				// Check if image exists in 'storage/products/' directory
				$imagePath = public_path('storage/products/' . $image);
				if (file_exists($imagePath)) {
					return asset('storage/products/' . $image);
				}

				// Check if image exists in the general 'storage/' directory
				$imagePath = public_path('storage/' . $image);
				if (file_exists($imagePath)) {
					return asset('storage/' . $image);
				}

				// If image doesn't exist in either directory, return a default placeholder or null
				return asset('storage/default-placeholder.jpg'); // Replace with a valid placeholder image
			});

			$product->tags = $product->tags;
			$product->producttypes = $product->producttypes;

			return $product;
		});

		return response()->json([
			'category' => $category,
			'products' => $products,
			'producttypes' => $productTypes,
		]);
	}

	// Function to get the full image URL for category images
	private function getCategoryImageUrl($image)
	{
		// Check if category image exists in 'storage/categories/' directory
		$imagePath = public_path('storage/categories/' . $image);
		if (file_exists($imagePath)) {
			return asset('storage/categories/' . $image);
		}

		// Check if image exists in the general 'storage/' directory
		$imagePath = public_path('storage/' . $image);
		if (file_exists($imagePath)) {
			return asset('storage/' . $image);
		}

		// If image doesn't exist in either directory, return a default placeholder or null
		return asset('storage/default-placeholder.jpg'); // Replace with a valid placeholder image
	}

	// public function getSpecificationFilters(Request $request)
	// {
	// 	$categoryId = $request->get('category_id');
	// 	$filters = $request->get('filters', []); // Filters from request
	// 	$perPage = $request->get('per_page', 10); // Default pagination

	// 	if (!$categoryId) {
	// 		return response()->json([
	// 			'success' => false,
	// 			'message' => 'Category ID is required',
	// 		], 200);
	// 	}

	// 	// Step 1: Fetch product IDs for the given category
	// 	$productIds = DB::table('ec_product_category_product')
	// 		->where('category_id', $categoryId)
	// 		->pluck('product_id');

	// 	if ($productIds->isEmpty()) {
	// 		return response()->json([
	// 			'success' => false,
	// 			'message' => 'No products found for this category',
	// 		], 200);
	// 	}

	// 	// Step 2: Fetch category-specific filters (spec_names)
	// 	$categoryFilters = DB::table('category_specifications')
	// 		->where('category_id', $categoryId)
	// 		->where('specification_type', 'Filters') // Use the new specification_type column
	// 		->pluck('specification_name');

	// 	$specifications = collect();

	// 	if ($categoryFilters->isNotEmpty()) {
	// 		// Step 3: Fetch specifications for these products and filters
	// 		$specifications = DB::table('specifications')
	// 			->whereIn('product_id', $productIds)
	// 			->whereIn('spec_name', $categoryFilters)
	// 			->get();
	// 	}

	// 	// Step 4: Apply filters (handling both ranges and specific values)
	// 	if (!empty($filters)) {
	// 		foreach ($filters as $filter) {
	// 			$specifications = $specifications->filter(function ($spec) use ($filter) {
	// 				$specNameMatch = $spec->spec_name === $filter['spec_name'];

	// 				// Handle range filters for numeric spec values
	// 				$rangeMatch = false;
	// 				if (isset($filter['min'], $filter['max'])) {
	// 					if (is_numeric($spec->spec_value)) {
	// 						$rangeMatch = $spec->spec_value >= $filter['min'] && $spec->spec_value <= $filter['max'];
	// 					}
	// 				} else {
	// 					$rangeMatch = true;
	// 				}

	// 				// Handle specific value filters for both numeric and non-numeric values
	// 				$valueMatch = isset($filter['spec_value'])
	// 					? (string)$spec->spec_value === (string)$filter['spec_value']
	// 					: true;

	// 				return $specNameMatch && ($rangeMatch || $valueMatch);
	// 			});
	// 		}

	// 		$productIds = $specifications->pluck('product_id')->unique();

	// 		if ($productIds->isEmpty()) {
	// 			return response()->json([
	// 				'success' => false,
	// 				'message' => 'No products match the selected filters',
	// 			], 200);
	// 		}
	// 	}

	// 	// Step 5: Fetch filtered products
	// 	$products = DB::table('ec_products')
	// 		->select([
	// 			'id', 'name', 'images', 'sku', 'price', 'sale_price', 'refund',
	// 			'delivery_days', 'currency_id',
	// 		])
	// 		->whereIn('id', $productIds)
	// 		->paginate($perPage);

	// 	// Step 6: Add specifications and ratings to products
	// 	$products->transform(function ($product) use ($specifications) {
	// 		$currency = DB::table('ec_currencies')->where('id', $product->currency_id)->first();
	// 		$product->currency_title = $currency
	// 			? ($currency->is_prefix_symbol
	// 				? $currency->title . ' ' . $product->price
	// 				: $product->price . ' ' . $currency->title)
	// 			: $product->price;

	// 		$totalReviews = DB::table('ec_reviews')->where('product_id', $product->id)->count();
	// 		$product->avg_rating = $totalReviews > 0
	// 			? DB::table('ec_reviews')->where('product_id', $product->id)->avg('star')
	// 			: null;

	// 		$product->specifications = $specifications->where('product_id', $product->id)->map(function ($spec) {
	// 			return [
	// 				'spec_name' => $spec->spec_name,
	// 				'spec_value' => $spec->spec_value,
	// 			];
	// 		});

	// 		$imagePaths = $product->images ? json_decode($product->images, true) : [];
	// 		$product->images = array_map(function ($imagePath) {
	// 			// Check if the URL starts with "http" or "https"
	// 			if (preg_match('/^(http|https):\/\//', $imagePath)) {
	// 				return $imagePath; // Return as is if it's an absolute URL
	// 			}
	// 			return asset('storage/' . $imagePath); // Otherwise, prepend storage path
	// 		}, $imagePaths);

	// 		return $product;
	// 	});

	// 	// Step 7: Create available filters (handling ranges and specific values)
	// 	$availableFilters = $specifications->isNotEmpty()
	// 		? $specifications->groupBy('spec_name')->map(function ($specs, $specName) {
	// 			$numericValues = $specs->pluck('spec_value')->filter(fn($value) => is_numeric($value))->unique()->sort()->values();
	// 			$nonNumericValues = $specs->pluck('spec_value')->filter(fn($value) => !is_numeric($value))->unique();

	// 			$ranges = [];
	// 			if ($numericValues->count() > 1) {
	// 				$minValue = $numericValues->first();
	// 				$maxValue = $numericValues->last();

	// 				$interval = ceil(($maxValue - $minValue) / 4);

	// 				for ($i = 0; $i < 4; $i++) {
	// 					$start = $minValue + $i * $interval;
	// 					$end = min($minValue + ($i + 1) * $interval, $maxValue);

	// 					$ranges[] = [
	// 						'min' => (int) $start,
	// 						'max' => (int) $end,
	// 					];
	// 				}
	// 			}

	// 			return [
	// 				'ranges' => $ranges,
	// 				'non_numeric_values' => $nonNumericValues,
	// 			];
	// 		})
	// 		: [];

	// 	return response()->json([
	// 		'success' => true,
	// 		'filters' => $availableFilters,
	// 		'products' => $products,
	// 	], 200);
	// }

	public function getSpecificationFilters(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'category_id' => 'required|integer',
			'applied_filters' => 'nullable|array',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'message' => $validator->errors()
			], 400);
		}
		$perPage = $request->get('per_page', 10); // Default pagination

		$category = ProductCategory::find($request->category_id);
		if (!$category) {
			return response()->json([
				'success' => false,
				'message' => 'Category does not exist.'
			], 400);
		}


		$categoryProductIds = $category->products->pluck('id')->all();
		if (!$categoryProductIds) {
			return response()->json([
				'success' => false,
				'message' => 'No product exist for this category.'
			], 400);
		}

		$categoryProducts = Product::select( 'id', 'name', 'images', 'sku', 'price', 'sale_price', 'refund', 'delivery_days', 'currency_id')->whereIn('id', $categoryProductIds)->with(['currency', 'reviews', 'specifications']);
		if ($request->applied_filters) {
			foreach ($request->applied_filters as $appliedFilter) {
				$categoryProducts->whereHas('specifications', function($query) use ($appliedFilter) {
					$query->where('spec_name', $appliedFilter['specification_name']);

					if ($appliedFilter['specification_type']=='fixed') {
						$query->where('spec_value', $appliedFilter['specification_value']);
					} elseif ($appliedFilter['specification_type']=='range') {
						$query->whereBetween('spec_value', [$appliedFilter['specification_value']['start'], $appliedFilter['specification_value']['end']]);
					}
				});
			}
		}

		$categoryProducts = $categoryProducts->paginate($perPage);

		// dd($categoryProducts->toArray());

		$modifiedProducts = $categoryProducts->getCollection()->map(function ($product) {
			$product->currency_title = $product->currency
			? ($product->currency->is_prefix_symbol
				? $product->currency->title . ' ' . $product->price
				: $product->price . ' ' . $product->currency->title)
			: $product->price;

			$product->avg_rating = $product->reviews->count() > 0
			? $product->reviews->avg('star')
			: null;

			$product->specifications = $product->specifications->map(function ($spec) {
				return [
					'spec_name' => $spec->spec_name,
					'spec_value' => $spec->spec_value,
				];
			});

			unset($product->currency, $product->reviews, $product->specifications);

			$imagePaths = is_array($product->images) ? $product->images : [];
			$product->images = array_map(function ($imagePath) {
				return preg_match('/^(http|https):\/\//', $imagePath)
				? $imagePath
				: asset('storage/' . $imagePath);
			}, $imagePaths);


			return $product;
		});

		$categoryProducts->setCollection($modifiedProducts);

		$categorySpecificationNames = $category->specifications
		->filter(function ($spec) {
			return strpos($spec['specification_type'], 'Filters') !== false;
		})->pluck('specification_name')->all();

		$specifications = Specification::whereIn('product_id', $categoryProductIds)->whereIn('spec_name', $categorySpecificationNames)->get();
		if ($specifications->count()) {
			$filters = collect($specifications)->groupBy('spec_name')->map(function ($group, $specName) {
				$values = $group->pluck('spec_value')->unique()->toArray();

				// Check if all values are numeric
				if (count($values) > 2 && collect($values)->every(fn($val) => is_numeric($val))) {
					// Convert values to integers
					$numericValues = collect($values)->map(fn($val) => (int) $val)->sort()->values();

					// Define the number of ranges (minimum 2, maximum 5)
					$totalRanges = min(max(2, ceil(count($numericValues) / 2)), 5);
					$chunkSize = ceil(count($numericValues) / $totalRanges);

					// Create range filters
					$ranges = $numericValues->chunk($chunkSize)->map(function ($chunk) {
						return [
							'min' => $chunk->first(),
							'max' => $chunk->last(),
						];
					})->values()->toArray();

					return [
						'specification_name' => $specName,
						'specification_type' => 'range',
						'specification_value' => $ranges,
					];
				} else {
					// Fixed filter (if there are strings or only two values)
					return [
						'specification_name' => $specName,
						'specification_type' => 'fixed',
						'specification_value' => array_values($values),
					];
				}
			})
			->values()
			->toArray();

		} else {
			[];
		}

		return response()->json([
			'success' => true,
			'filters' => $filters,
			'products' => $categoryProducts,
		], 200);
	}
}
