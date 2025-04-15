<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Botble\Ecommerce\Models\Brand;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\AttributeValue;
use Botble\Ecommerce\Models\Attribute;
use Botble\Ecommerce\Models\AttributeGroup;
use Botble\Ecommerce\Models\ProductAttribute;
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
	// 	$validator = Validator::make($request->all(), [
	// 		'category_id' => 'required|integer',
	// 		'applied_filters' => 'nullable|array',
	// 		'price_min' => 'nullable|numeric|min:0',
	// 		'price_max' => 'nullable|numeric|min:0',
	// 		'price_order' => 'nullable|in:high_to_low,low_to_high',
	// 		'brand_id' => 'nullable|array', // Update this line
	// 		'brand_id.*' => 'integer', // Validate all brand_id elements as integers
	// 		'rating' => 'nullable|numeric|min:1|max:5',
	// 	]);
	
	// 	if ($validator->fails()) {
	// 		return response()->json([
	// 			'success' => false,
	// 			'message' => $validator->errors()
	// 		], 400);
	// 	}
	
	// 	$perPage = $request->get('per_page', 10);
	
	// 	$category = ProductCategory::find($request->category_id);
	// 	if (!$category) {
	// 		return response()->json([
	// 			'success' => false,
	// 			'message' => 'Category does not exist.'
	// 		], 400);
	// 	}
	
	// 	$categoryProductIds = $category->products->pluck('id')->all();
	// 	if (!$categoryProductIds) {
	// 		return response()->json([
	// 			'success' => false,
	// 			'message' => 'No product exist for this category.'
	// 		], 400);
	// 	}
	
	// 	// Get sort parameter
	// 	$sortBy = $request->input('sort_by', 'created_at');
	// 	if (!in_array($sortBy, ['created_at', 'price', 'name'])) {
	// 		$sortBy = 'created_at';
	// 	}
	// 	$sortByType = $request->input('sort_by_type', 'desc');
	// 	if (!in_array($sortByType, ['asc', 'desc'])) {
	// 		$sortByType = 'desc';
	// 	}
	
	// 	// First, if rating filter is applied, get the product IDs that match the rating criteria
	// 	$ratingFilteredIds = collect($categoryProductIds);
	// 	if ($request->has('rating') && $request->rating) {
	// 		$ratingValue = $request->rating;
			
	// 		// Get products with the required average rating
	// 		$ratingFilteredIds = DB::table('ec_reviews')
	// 			->whereIn('product_id', $categoryProductIds)
	// 			->select('product_id')
	// 			->groupBy('product_id')
	// 			->havingRaw('ROUND(AVG(star)) = ?', [$ratingValue])
	// 			->pluck('product_id');
				
	// 		// If no products match the rating, return empty result early
	// 		if ($ratingFilteredIds->isEmpty()) {
	// 			return response()->json([
	// 				'success' => true,
	// 				'filters' => [],
	// 				'products' => [
	// 					'data' => [],
	// 					'total' => 0,
	// 					'per_page' => $perPage,
	// 					'current_page' => 1,
	// 					'last_page' => 1
	// 				],
	// 				'brands' => [],
	// 				'rating_filter' => [
	// 					'filter_name' => 'Rating',
	// 					'filter_type' => 'rating',
	// 					'filter_values' => [5, 4, 3, 2, 1]
	// 				],
	// 			], 200);
	// 		}
	// 	}
	
	// 	// Now use the filtered product IDs
	// 	$categoryProducts = Product::select('id', 'name', 'images', 'sku', 'price', 'sale_price', 'refund', 'delivery_days', 'currency_id', 'brand_id')
	// 							   ->whereIn('id', $ratingFilteredIds)
	// 							   ->with(['currency', 'reviews', 'specifications', 'brand']);
		
	// 	// Apply specification filters
	// 	if ($request->applied_filters) {
	// 		foreach ($request->applied_filters as $appliedFilter) {
	// 			$categoryProducts->whereHas('specifications', function($query) use ($appliedFilter) {
	// 				$query->where('spec_name', $appliedFilter['specification_name']);
	
	// 				if ($appliedFilter['specification_type']=='fixed') {
	// 					$query->where('spec_value', $appliedFilter['specification_value']);
	// 				} elseif ($appliedFilter['specification_type']=='range') {
	// 					$query->whereBetween('spec_value', [$appliedFilter['specification_value']['start'], $appliedFilter['specification_value']['end']]);
	// 				}
	// 			});
	// 		}
	// 	}
	
	// 	// Apply price filter
	// 	if ($request->has('price_min') || $request->has('price_max')) {
	// 		$priceMin = $request->input('price_min', 0);
	// 		$priceMax = $request->input('price_max', PHP_INT_MAX);
	// 		$categoryProducts->whereRaw("COALESCE(sale_price, price) BETWEEN ? AND ?", [$priceMin, $priceMax]);
	// 	}
	
	// 	// Apply brand filter
	// 	if ($request->has('brand_id') && $request->brand_id) {
	// 		$categoryProducts->whereIn('brand_id', $request->brand_id); // Updated to handle array
	// 	}
	
	// 	// Apply sorting
	// 	if ($sortBy == 'price') {
	// 		$categoryProducts = $categoryProducts->orderByRaw("COALESCE(sale_price, price) $sortByType");
	// 	} else {
	// 		$categoryProducts = $categoryProducts->orderBy($sortBy, $sortByType);
	// 	}
	
	// 	$categoryProducts = $categoryProducts->paginate($perPage);
	
	// 	$modifiedProducts = $categoryProducts->getCollection()->map(function ($product) {
	// 		$product->currency_title = $product->currency ? $product->currency->title : '';
	
	// 		// Calculate and round the average rating
	// 		$rawAvgRating = $product->reviews->count() > 0 ? $product->reviews->avg('star') : null;
	// 		$product->avg_rating = $rawAvgRating ? round($rawAvgRating) : null;
	
	// 		$product->brand_name = $product->brand ? $product->brand->name : null;
	
	// 		$product->specifications = $product->specifications->map(function ($spec) {
	// 			return [
	// 				'spec_name' => $spec->spec_name,
	// 				'spec_value' => $spec->spec_value,
	// 			];
	// 		});
	
	// 		unset($product->currency, $product->reviews, $product->brand);
	
	// 		$imagePaths = is_array($product->images) ? $product->images : [];
	// 		$product->images = array_map(function ($imagePath) {
	// 			return preg_match('/^(http|https):\/\//', $imagePath)
	// 				? $imagePath
	// 				: asset('storage/' . $imagePath);
	// 		}, $imagePaths);
	
	// 		return $product;
	// 	});
	
	// 	$categoryProducts->setCollection($modifiedProducts);
	
	// 	// Get specifications for filtering
	// 	$categorySpecificationNames = $category->specifications
	// 		->filter(function ($spec) {
	// 			return strpos($spec['specification_type'], 'Filters') !== false;
	// 		})
	// 		->pluck('specification_name')->all();
	
	// 	$specifications = Specification::whereIn('product_id', $categoryProductIds)->whereIn('spec_name', $categorySpecificationNames)->get();
	// 	$filters = [];
	// 	if ($specifications->count()) {
	// 		$filters = collect($specifications)->groupBy('spec_name')->map(function ($group, $specName) {
	// 			$values = $group->pluck('spec_value')->unique()->toArray();
	
	// 			// Check if all values are numeric
	// 			if (count($values) > 2 && collect($values)->every(fn($val) => is_numeric($val))) {
	// 				// Convert values to integers
	// 				$numericValues = collect($values)->map(fn($val) => (int) $val)->sort()->values();
	
	// 				// Define the number of ranges
	// 				$totalRanges = min(max(2, ceil(count($numericValues) / 2)), 5);
	// 				$chunkSize = ceil(count($numericValues) / $totalRanges);
	
	// 				// Create range filters
	// 				$ranges = $numericValues->chunk($chunkSize)->map(function ($chunk) {
	// 					return [
	// 						'min' => $chunk->first(),
	// 						'max' => $chunk->last(),
	// 					];
	// 				})->values()->toArray();
	
	// 				return [
	// 					'specification_name' => $specName,
	// 					'specification_type' => 'range',
	// 					'specification_value' => $ranges,
	// 				];
	// 			} else {
	// 				// Fixed filter
	// 				return [
	// 					'specification_name' => $specName,
	// 					'specification_type' => 'fixed',
	// 					'specification_value' => array_values($values),
	// 				];
	// 			}
	// 		})
	// 		->values()
	// 		->toArray();
	// 	}
	
	// 	// Get only brands that exist in this category's products
	// 	$categoryBrandIds = Product::whereIn('id', $categoryProductIds)
	// 							   ->whereNotNull('brand_id')
	// 							   ->pluck('brand_id')
	// 							   ->unique();
		
	// 	$brands = Brand::select('id', 'name')
	// 				  ->whereIn('id', $categoryBrandIds)
	// 				  ->get();
	
	// 	// Create rating filter options
	// 	$ratingFilter = [
	// 		'filter_name' => 'Rating',
	// 		'filter_type' => 'rating',
	// 		'filter_values' => [5, 4, 3, 2, 1]
	// 	];
	
	// 	return response()->json([
	// 		'success' => true,
	// 		'filters' => $filters,
	// 		'products' => $categoryProducts,
	// 		'brands' => $brands,
	// 		'rating_filter' => $ratingFilter,
	// 	], 200);
	// }

// 	public function getSpecificationFilters(Request $request)
// {
//     // Existing validation code
//     $validator = Validator::make($request->all(), [
//         'category_id' => 'required|integer',
//         'filters' => 'nullable|array',
//         'price_min' => 'nullable|numeric|min:0',
//         'price_max' => 'nullable|numeric|min:0',
//         'price_order' => 'nullable|in:high_to_low,low_to_high',
//         'brand_id' => 'nullable|array',
//         'brand_id.*' => 'integer',
//         'rating' => 'nullable|numeric|min:1|max:5',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['success' => false, 'message' => $validator->errors()], 400);
//     }

//     $perPage = $request->get('per_page', 10);
//     $category = ProductCategory::find($request->category_id);
//     if (!$category) {
//         return response()->json(['success' => false, 'message' => 'Category does not exist.'], 400);
//     }

//     $categoryProductIds = $category->products->pluck('id')->all();
//     if (empty($categoryProductIds)) {
//         return response()->json([
//             'success' => true,
//             'filters' => [],
//             'products' => [],
//             'brands' => [],
//             'rating_filter' => [
//                 'filter_name' => 'Rating',
//                 'filter_type' => 'rating',
//                 'filter_values' => [5, 4, 3, 2, 1],
//             ],
//         ]);
//     }

//     // Start with all category product IDs
//     $filteredProductIds = collect($categoryProductIds);

//     // Apply attribute filters if provided
//     if ($request->has('filters') && is_array($request->filters)) {
//         foreach ($request->filters as $filter) {
//             if (!isset($filter['specification_name']) || !isset($filter['specification_value']) || empty($filter['specification_value'])) {
//                 continue;
//             }

//             $specName = $filter['specification_name'];
//             $specValues = is_array($filter['specification_value']) ? $filter['specification_value'] : [$filter['specification_value']];

//             // Find attribute ID based on name
//             $attribute = Attribute::where('name', $specName)->first();
//             if (!$attribute) {
//                 continue;
//             }

//             // Find product IDs that match this attribute and values
//             $matchingProductIds = DB::table('product_attributes as pa')
//                 ->where('pa.attribute_id', $attribute->id)
//                 ->whereIn('pa.attribute_value', $specValues)
//                 ->whereIn('pa.product_id', $filteredProductIds)
//                 ->pluck('pa.product_id')
//                 ->unique();

//             // Intersect with our running list of product IDs
//             $filteredProductIds = $filteredProductIds->intersect($matchingProductIds);

//             // If no products match these filters, return empty results early
//             if ($filteredProductIds->isEmpty()) {
//                 return response()->json([
//                     'success' => true,
//                     'filters' => [],
//                     'products' => [],
//                     'brands' => [],
//                     'rating_filter' => [
//                         'filter_name' => 'Rating',
//                         'filter_type' => 'rating',
//                         'filter_values' => [5, 4, 3, 2, 1],
//                     ],
//                 ]);
//             }
//         }
//     }

//     // If a rating filter is applied, filter the already filtered product IDs
//     if ($request->has('rating') && $request->rating) {
//         $ratingFilteredIds = DB::table('ec_reviews')
//             ->whereIn('product_id', $filteredProductIds)
//             ->select('product_id')
//             ->groupBy('product_id')
//             ->havingRaw('ROUND(AVG(star)) = ?', [$request->rating])
//             ->pluck('product_id');

//         $filteredProductIds = $filteredProductIds->intersect($ratingFilteredIds);

//         if ($filteredProductIds->isEmpty()) {
//             return response()->json([
//                 'success' => true,
//                 'filters' => [],
//                 'products' => [],
//                 'brands' => [],
//                 'rating_filter' => [
//                     'filter_name' => 'Rating',
//                     'filter_type' => 'rating',
//                     'filter_values' => [5, 4, 3, 2, 1],
//                 ],
//             ]);
//         }
//     }

//     // Fetching products based on filters
//     $products = Product::whereIn('id', $filteredProductIds)
//         ->with(['currency', 'reviews', 'brand'])
//         ->when($request->has('price_min') || $request->has('price_max'), function ($query) use ($request) {
//             $min = $request->input('price_min', 0);
//             $max = $request->input('price_max', PHP_INT_MAX);
//             return $query->whereRaw("COALESCE(sale_price, price) BETWEEN ? AND ?", [$min, $max]);
//         })
//         ->when($request->has('brand_id') && $request->brand_id, function ($query) use ($request) {
//             return $query->whereIn('brand_id', $request->brand_id);
//         });

//     // Apply sorting
//     $sortBy = $request->input('sort_by', 'created_at');
//     $sortByType = $request->input('sort_by_type', 'desc');
//     if ($sortBy == 'price') {
//         $products = $products->orderByRaw("COALESCE(sale_price, price) $sortByType");
//     } else {
//         $products = $products->orderBy($sortBy, $sortByType);
//     }

//     $paginatedProducts = $products->paginate($perPage);
//     $modifiedProducts = $paginatedProducts->getCollection()->map(function ($product) {
//         $product->currency_title = optional($product->currency)->title;
//         $product->avg_rating = $product->reviews->count() > 0 ? round($product->reviews->avg('star')) : null;
//         $product->brand_name = optional($product->brand)->name;

//         $product->images = collect(is_array($product->images) ? $product->images : [])->map(function ($img) {
//             return preg_match('/^(http|https):\/\//', $img) ? $img : asset('storage/' . $img);
//         })->toArray();

//         unset($product->currency, $product->reviews, $product->brand);
//         return $product;
//     });

//     $paginatedProducts->setCollection($modifiedProducts);

//     // Get allowed attribute IDs from the sub_categories table for this category
//     $subCategory = DB::table('sub_categories')
//         ->where('category_id', $request->category_id)
//         ->first();
    
//     // Initialize debug info
//     $debugInfo = [
//         'category_id' => $request->category_id,
//         'has_subcategory' => $subCategory ? true : false,
//         'raw_attributes_ids' => $subCategory ? $subCategory->attributes_ids : null,
//         'category_product_count' => count($categoryProductIds),
//         'has_products_with_attributes' => false,
//         'attribute_ids_parsed' => [],
//         'products_with_attributes' => []
//     ];
    
//     // Initialize filters array
//     $filters = [];
    
//     // Only process attribute filters if the subcategory exists and has attributes_ids
//     if ($subCategory) {
//         // Check if we have attributes_ids column (might be attributes_jd in your case)
//         if (property_exists($subCategory, 'attributes_ids') || isset($subCategory->attributes_ids)) {
//             if (!empty($subCategory->attributes_ids)) {
//                 // Get the allowed attribute IDs
//                 $allowedAttributeIds = [];
                
//                 // Assuming attributes_ids is stored as a JSON array or comma-separated string
//                 if (is_string($subCategory->attributes_ids)) {
//                     $allowedAttributeIds = json_decode($subCategory->attributes_ids, true);
//                     $debugInfo['json_decode_error'] = json_last_error_msg();
                    
//                     // If it's a string but not JSON format (maybe comma-separated)
//                     if (json_last_error() !== JSON_ERROR_NONE) {
//                         $allowedAttributeIds = explode(',', $subCategory->attributes_ids);
//                         $debugInfo['using_comma_separated'] = true;
//                     }
//                     // Special case: Check if we have an array containing a single string with comma-separated values
//                     else if (count($allowedAttributeIds) === 1 && is_string($allowedAttributeIds[0]) && strpos($allowedAttributeIds[0], ',') !== false) {
//                         $allowedAttributeIds = explode(',', $allowedAttributeIds[0]);
//                         $debugInfo['using_nested_comma_separated'] = true;
//                     }
//                 } else {
//                     $allowedAttributeIds = $subCategory->attributes_ids;
//                 }
                
//                 // Ensure we have an array of integers
//                 $allowedAttributeIds = array_map('intval', (array)$allowedAttributeIds);
//                 $debugInfo['attribute_ids_parsed'] = $allowedAttributeIds;
                
//                 // Only proceed if we have valid attribute IDs
//                 if (!empty($allowedAttributeIds)) {
//                     // Check if ANY products have these attributes
//                     $attributeProductCount = DB::table('product_attributes as pa')
//                         ->whereIn('pa.product_id', $categoryProductIds)
//                         ->whereIn('pa.attribute_id', $allowedAttributeIds)
//                         ->count(DB::raw('DISTINCT pa.product_id'));
                    
//                     $debugInfo['has_products_with_attributes'] = $attributeProductCount > 0;
//                     $debugInfo['products_with_attributes_count'] = $attributeProductCount;
                    
//                     // Get a sample of products with their attribute IDs for debugging
//                     $sampleProducts = DB::table('product_attributes as pa')
//                         ->whereIn('pa.product_id', $categoryProductIds)
//                         ->select('pa.product_id', 'pa.attribute_id')
//                         ->limit(5)
//                         ->get();
                    
//                     $debugInfo['sample_product_attributes'] = $sampleProducts;
                    
//                     // Get attribute filters for this category, but only for the allowed attributes
//                     $attributeValues = DB::table('product_attributes as pa')
//                         ->join('attributes as at', 'at.id', '=', 'pa.attribute_id')
//                         ->whereIn('pa.product_id', $categoryProductIds)
//                         ->whereIn('pa.attribute_id', $allowedAttributeIds)
//                         ->select('at.name as attribute_name', 'pa.attribute_value', 'at.id as attribute_id')
//                         ->get();
                        
//                     $debugInfo['attribute_values_count'] = $attributeValues->count();
                    
//                     // If we have any attribute values
//                     if ($attributeValues->count() > 0) {
//                         $attributeValues = $attributeValues->groupBy('attribute_name');
                        
//                         // Process attribute filters
//                         foreach ($attributeValues as $attributeName => $values) {
//                             $uniqueValues = $values->pluck('attribute_value')->unique()->filter()->values();

//                             if ($uniqueValues->every(fn($val) => is_numeric($val)) && $uniqueValues->count() > 2) {
//                                 $sorted = $uniqueValues->map(fn($val) => (float)$val)->sort()->values();
//                                 $chunkCount = min(5, ceil($sorted->count() / 2));
//                                 $chunkSize = ceil($sorted->count() / $chunkCount);

//                                 $ranges = $sorted->chunk($chunkSize)->map(function ($chunk) {
//                                     return [
//                                         'min' => $chunk->first(),
//                                         'max' => $chunk->last(),
//                                     ];
//                                 })->toArray();

//                                 $filters[] = [
//                                     'specification_name' => $attributeName,
//                                     'specification_type' => 'range',
//                                     'specification_value' => $ranges,
//                                 ];
//                             } else {
//                                 $filters[] = [
//                                     'specification_name' => $attributeName,
//                                     'specification_type' => 'fixed',
//                                     'specification_value' => $uniqueValues->values(),
//                                 ];
//                             }
//                         }
//                     }
//                 }
//             } else {
//                 $debugInfo['attributes_ids_empty'] = true;
//             }
//         } else if (property_exists($subCategory, 'attributes_jd') || isset($subCategory->attributes_jd)) {
//             // If column is attributes_jd instead of attributes_ids
//             $debugInfo['using_attributes_jd_instead'] = true;
            
//             if (!empty($subCategory->attributes_jd)) {
//                 // Similar processing to above but for attributes_jd
//                 $allowedAttributeIds = [];
                
//                 if (is_string($subCategory->attributes_jd)) {
//                     $allowedAttributeIds = json_decode($subCategory->attributes_jd, true);
//                     $debugInfo['json_decode_error_jd'] = json_last_error_msg();
                    
//                     if (json_last_error() !== JSON_ERROR_NONE) {
//                         $allowedAttributeIds = explode(',', $subCategory->attributes_jd);
//                         $debugInfo['using_comma_separated_jd'] = true;
//                     } 
//                     // Special case: Check if we have an array containing a single string with comma-separated values
//                     else if (count($allowedAttributeIds) === 1 && is_string($allowedAttributeIds[0]) && strpos($allowedAttributeIds[0], ',') !== false) {
//                         $allowedAttributeIds = explode(',', $allowedAttributeIds[0]);
//                         $debugInfo['using_nested_comma_separated_jd'] = true;
//                     }
//                 } else {
//                     $allowedAttributeIds = $subCategory->attributes_jd;
//                 }
                
//                 $allowedAttributeIds = array_map('intval', (array)$allowedAttributeIds);
//                 $debugInfo['attribute_ids_parsed_jd'] = $allowedAttributeIds;
                
//                 // Only proceed if we have valid attribute IDs
//                 if (!empty($allowedAttributeIds)) {
//                     // Check if ANY products have these attributes
//                     $attributeProductCount = DB::table('product_attributes as pa')
//                         ->whereIn('pa.product_id', $categoryProductIds)
//                         ->whereIn('pa.attribute_id', $allowedAttributeIds)
//                         ->count(DB::raw('DISTINCT pa.product_id'));
                    
//                     $debugInfo['has_products_with_attributes'] = $attributeProductCount > 0;
//                     $debugInfo['products_with_attributes_count'] = $attributeProductCount;
                    
//                     // Get attribute filters for this category, but only for the allowed attributes
//                     $attributeValues = DB::table('product_attributes as pa')
//                         ->join('attributes as at', 'at.id', '=', 'pa.attribute_id')
//                         ->whereIn('pa.product_id', $categoryProductIds)
//                         ->whereIn('pa.attribute_id', $allowedAttributeIds)
//                         ->select('at.name as attribute_name', 'pa.attribute_value', 'at.id as attribute_id')
//                         ->get();
                        
//                     $debugInfo['attribute_values_count'] = $attributeValues->count();
                    
//                     // If we have any attribute values
//                     if ($attributeValues->count() > 0) {
//                         $attributeValues = $attributeValues->groupBy('attribute_name');
                        
//                         // Process attribute filters
//                         foreach ($attributeValues as $attributeName => $values) {
//                             $uniqueValues = $values->pluck('attribute_value')->unique()->filter()->values();

//                             if ($uniqueValues->every(fn($val) => is_numeric($val)) && $uniqueValues->count() > 2) {
//                                 $sorted = $uniqueValues->map(fn($val) => (float)$val)->sort()->values();
//                                 $chunkCount = min(5, ceil($sorted->count() / 2));
//                                 $chunkSize = ceil($sorted->count() / $chunkCount);

//                                 $ranges = $sorted->chunk($chunkSize)->map(function ($chunk) {
//                                     return [
//                                         'min' => $chunk->first(),
//                                         'max' => $chunk->last(),
//                                     ];
//                                 })->toArray();

//                                 $filters[] = [
//                                     'specification_name' => $attributeName,
//                                     'specification_type' => 'range',
//                                     'specification_value' => $ranges,
//                                 ];
//                             } else {
//                                 $filters[] = [
//                                     'specification_name' => $attributeName,
//                                     'specification_type' => 'fixed',
//                                     'specification_value' => $uniqueValues->values(),
//                                 ];
//                             }
//                         }
//                     }
//                 }
//             }
//         }
//     }

//     $brandIds = Product::whereIn('id', $categoryProductIds)->whereNotNull('brand_id')->pluck('brand_id')->unique();
//     $brands = Brand::whereIn('id', $brandIds)->select('id', 'name')->get();

//     $ratingFilter = [
//         'filter_name' => 'Rating',
//         'filter_type' => 'rating',
//         'filter_values' => [5, 4, 3, 2, 1],
//     ];

//     // For debugging purposes, add a debug key to the response
//     return response()->json([
//         'success' => true,
//         'filters' => $filters,
//         'products' => $paginatedProducts,
//         'brands' => $brands,
//         'rating_filter' => $ratingFilter,
//         'debug_info' => $debugInfo  // Include debug info in the response
//     ]);
// }

// public function getSpecificationFilters(Request $request)
// {
//     // Existing validation code
//     $validator = Validator::make($request->all(), [
//         'category_id' => 'required|integer',
//         'filters' => 'nullable|array',
//         'price_min' => 'nullable|numeric|min:0',
//         'price_max' => 'nullable|numeric|min:0',
//         'price_order' => 'nullable|in:high_to_low,low_to_high',
//         'brand_id' => 'nullable|array',
//         'brand_id.*' => 'integer',
//         'rating' => 'nullable|numeric|min:1|max:5',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['success' => false, 'message' => $validator->errors()], 400);
//     }

//     $perPage = $request->get('per_page', 10);
//     $category = ProductCategory::find($request->category_id);
//     if (!$category) {
//         return response()->json(['success' => false, 'message' => 'Category does not exist.'], 400);
//     }

//     $categoryProductIds = $category->products->pluck('id')->all();
//     if (empty($categoryProductIds)) {
//         return response()->json([
//             'success' => true,
//             'filters' => [],
//             'products' => [],
//             'brands' => [],
//             'rating_filter' => [
//                 'filter_name' => 'Rating',
//                 'filter_type' => 'rating',
//                 'filter_values' => [5, 4, 3, 2, 1],
//             ],
//         ]);
//     }

//     // Start with all category product IDs
//     $filteredProductIds = collect($categoryProductIds);

//     // Apply attribute filters if provided
//     if ($request->has('filters') && is_array($request->filters)) {
//         foreach ($request->filters as $filter) {
//             if (!isset($filter['specification_name']) || !isset($filter['specification_value']) || empty($filter['specification_value'])) {
//                 continue;
//             }

//             $specName = $filter['specification_name'];
//             $specValues = is_array($filter['specification_value']) ? $filter['specification_value'] : [$filter['specification_value']];

//             // Find attribute ID based on name
//             $attribute = Attribute::where('name', $specName)->first();
//             if (!$attribute) {
//                 continue;
//             }

//             // Find product IDs that match this attribute and values
//             $matchingProductIds = DB::table('product_attributes as pa')
//                 ->where('pa.attribute_id', $attribute->id)
//                 ->whereIn('pa.attribute_value', $specValues)
//                 ->whereIn('pa.product_id', $filteredProductIds)
//                 ->pluck('pa.product_id')
//                 ->unique();

//             // Intersect with our running list of product IDs
//             $filteredProductIds = $filteredProductIds->intersect($matchingProductIds);

//             // If no products match these filters, return empty results early
//             if ($filteredProductIds->isEmpty()) {
//                 return response()->json([
//                     'success' => true,
//                     'filters' => [],
//                     'products' => [],
//                     'brands' => [],
//                     'rating_filter' => [
//                         'filter_name' => 'Rating',
//                         'filter_type' => 'rating',
//                         'filter_values' => [5, 4, 3, 2, 1],
//                     ],
//                 ]);
//             }
//         }
//     }

//     // If a rating filter is applied, filter the already filtered product IDs
//     if ($request->has('rating') && $request->rating) {
//         $ratingFilteredIds = DB::table('ec_reviews')
//             ->whereIn('product_id', $filteredProductIds)
//             ->select('product_id')
//             ->groupBy('product_id')
//             ->havingRaw('ROUND(AVG(star)) = ?', [$request->rating])
//             ->pluck('product_id');

//         $filteredProductIds = $filteredProductIds->intersect($ratingFilteredIds);

//         if ($filteredProductIds->isEmpty()) {
//             return response()->json([
//                 'success' => true,
//                 'filters' => [],
//                 'products' => [],
//                 'brands' => [],
//                 'rating_filter' => [
//                     'filter_name' => 'Rating',
//                     'filter_type' => 'rating',
//                     'filter_values' => [5, 4, 3, 2, 1],
//                 ],
//             ]);
//         }
//     }

//     // Fetching products based on filters
//     $products = Product::whereIn('id', $filteredProductIds)
//         ->with(['currency', 'reviews', 'brand'])
//         ->when($request->has('price_min') || $request->has('price_max'), function ($query) use ($request) {
//             $min = $request->input('price_min', 0);
//             $max = $request->input('price_max', PHP_INT_MAX);
//             return $query->whereRaw("COALESCE(sale_price, price) BETWEEN ? AND ?", [$min, $max]);
//         })
//         ->when($request->has('brand_id') && $request->brand_id, function ($query) use ($request) {
//             return $query->whereIn('brand_id', $request->brand_id);
//         });

//     // Apply sorting
//     $sortBy = $request->input('sort_by', 'created_at');
//     $sortByType = $request->input('sort_by_type', 'desc');
//     if ($sortBy == 'price') {
//         $products = $products->orderByRaw("COALESCE(sale_price, price) $sortByType");
//     } else {
//         $products = $products->orderBy($sortBy, $sortByType);
//     }

//     $paginatedProducts = $products->paginate($perPage);
//     $modifiedProducts = $paginatedProducts->getCollection()->map(function ($product) {
//         $product->currency_title = optional($product->currency)->title;
//         $product->avg_rating = $product->reviews->count() > 0 ? round($product->reviews->avg('star')) : null;
//         $product->brand_name = optional($product->brand)->name;

//         $product->images = collect(is_array($product->images) ? $product->images : [])->map(function ($img) {
//             return preg_match('/^(http|https):\/\//', $img) ? $img : asset('storage/' . $img);
//         })->toArray();

//         unset($product->currency, $product->reviews, $product->brand);
//         return $product;
//     });

//     $paginatedProducts->setCollection($modifiedProducts);

//     // Initialize filters array - will remain empty if subcategory doesn't exist
//     $filters = [];
    
//     // Initialize debug info
//     $debugInfo = [
//         'category_id' => $request->category_id,
//         'category_product_count' => count($categoryProductIds),
//     ];
    
//     // Get subcategory for this category
//     $subCategory = DB::table('sub_categories')
//         ->where('category_id', $request->category_id)
//         ->first();
    
//     $debugInfo['has_subcategory'] = $subCategory ? true : false;
    
//     // Only process attribute filters if the subcategory exists
//     if ($subCategory) {
//         $attributeIdsField = null;
//         $attributeIds = [];
        
//         // Check which attribute ID field exists
//         if (property_exists($subCategory, 'attributes_ids') || isset($subCategory->attributes_ids)) {
//             $attributeIdsField = 'attributes_ids';
//         } else if (property_exists($subCategory, 'attributes_jd') || isset($subCategory->attributes_jd)) {
//             $attributeIdsField = 'attributes_jd';
//         }
        
//         $debugInfo['attribute_ids_field'] = $attributeIdsField;
        
//         // Process attribute IDs if the field exists and has value
//         if ($attributeIdsField && !empty($subCategory->$attributeIdsField)) {
//             $attributeIdsValue = $subCategory->$attributeIdsField;
            
//             // Parse attribute IDs based on data type
//             if (is_string($attributeIdsValue)) {
//                 $attributeIds = json_decode($attributeIdsValue, true);
//                 $debugInfo['json_decode_error'] = json_last_error_msg();
                
//                 // If it's not valid JSON, try comma-separated format
//                 if (json_last_error() !== JSON_ERROR_NONE) {
//                     $attributeIds = explode(',', $attributeIdsValue);
//                     $debugInfo['using_comma_separated'] = true;
//                 }
//                 // Special case: Check if we have an array containing a single string with comma-separated values
//                 else if (count($attributeIds) === 1 && is_string($attributeIds[0]) && strpos($attributeIds[0], ',') !== false) {
//                     $attributeIds = explode(',', $attributeIds[0]);
//                     $debugInfo['using_nested_comma_separated'] = true;
//                 }
//             } else {
//                 $attributeIds = $attributeIdsValue;
//             }
            
//             // Ensure we have an array of integers
//             $attributeIds = array_map('intval', (array)$attributeIds);
//             $debugInfo['attribute_ids_parsed'] = $attributeIds;
            
//             // Only proceed if we have valid attribute IDs
//             if (!empty($attributeIds)) {
//                 // Get attribute filters for this category, but only for the allowed attributes
//                 $attributeValues = DB::table('product_attributes as pa')
//                     ->join('attributes as at', 'at.id', '=', 'pa.attribute_id')
//                     ->whereIn('pa.product_id', $categoryProductIds)
//                     ->whereIn('pa.attribute_id', $attributeIds)
//                     ->select('at.name as attribute_name', 'pa.attribute_value', 'at.id as attribute_id')
//                     ->get();
                    
//                 $debugInfo['attribute_values_count'] = $attributeValues->count();
                
//                 // If we have any attribute values
//                 if ($attributeValues->count() > 0) {
//                     $attributeValues = $attributeValues->groupBy('attribute_name');
                    
//                     // Process attribute filters
//                     foreach ($attributeValues as $attributeName => $values) {
//                         $uniqueValues = $values->pluck('attribute_value')->unique()->filter()->values();

//                         if ($uniqueValues->every(fn($val) => is_numeric($val)) && $uniqueValues->count() > 2) {
//                             $sorted = $uniqueValues->map(fn($val) => (float)$val)->sort()->values();
//                             $chunkCount = min(5, ceil($sorted->count() / 2));
//                             $chunkSize = ceil($sorted->count() / $chunkCount);

//                             $ranges = $sorted->chunk($chunkSize)->map(function ($chunk) {
//                                 return [
//                                     'min' => $chunk->first(),
//                                     'max' => $chunk->last(),
//                                 ];
//                             })->toArray();

//                             $filters[] = [
//                                 'specification_name' => $attributeName,
//                                 'specification_type' => 'range',
//                                 'specification_value' => $ranges,
//                             ];
//                         } else {
//                             $filters[] = [
//                                 'specification_name' => $attributeName,
//                                 'specification_type' => 'fixed',
//                                 'specification_value' => $uniqueValues->values(),
//                             ];
//                         }
//                     }
//                 }
//             }
//         } else {
//             $debugInfo['attributes_field_empty'] = true;
//         }
//     }

//     $brandIds = Product::whereIn('id', $categoryProductIds)->whereNotNull('brand_id')->pluck('brand_id')->unique();
//     $brands = Brand::whereIn('id', $brandIds)->select('id', 'name')->get();

//     $ratingFilter = [
//         'filter_name' => 'Rating',
//         'filter_type' => 'rating',
//         'filter_values' => [5, 4, 3, 2, 1],
//     ];

//     // For debugging purposes, add a debug key to the response
//     return response()->json([
//         'success' => true,
//         'filters' => $filters,
//         'products' => $paginatedProducts,
//         'brands' => $brands,
//         'rating_filter' => $ratingFilter,
//         'debug_info' => $debugInfo
//     ]);
// }

public function getSpecificationFilters(Request $request)
{
    // Existing validation code
    $validator = Validator::make($request->all(), [
        'category_id' => 'required|integer',
        'filters' => 'nullable|array',
        'price_min' => 'nullable|numeric|min:0',
        'price_max' => 'nullable|numeric|min:0',
        'price_order' => 'nullable|in:high_to_low,low_to_high',
        'brand_id' => 'nullable|array',
        'brand_id.*' => 'integer',
        'rating' => 'nullable|numeric|min:1|max:5',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'message' => $validator->errors()], 400);
    }

    $perPage = $request->get('per_page', 10);
    $category = ProductCategory::find($request->category_id);
    if (!$category) {
        return response()->json(['success' => false, 'message' => 'Category does not exist.'], 400);
    }

    // Get products from current category
    // $currentCategoryProducts = $category->products->pluck('id')->all();
    $currentCategoryProducts = $category->products()->where('status', 'published')->pluck('id')->all();
    // Get all child categories based on parent_id
    $childCategories = ProductCategory::where('parent_id', $category->id)->get();
    $childCategoryIds = $childCategories->pluck('id')->toArray();
    
    // Get all products from child categories
    $childProductIds = [];
    if (!empty($childCategoryIds)) {
        // Using a relationship between categories and products
        foreach ($childCategories as $childCategory) {
            // $childProductIds = array_merge($childProductIds, $childCategory->products->pluck('id')->all());
			$childProductIds = array_merge($childProductIds, $childCategory->products()->where('status', 'published')->pluck('id')->all());
        }
    }
    
    // Combine products from current category and all child categories
    $allCategoryProductIds = array_unique(array_merge($currentCategoryProducts, $childProductIds));
    
    // Debug info for verification
    $debugInfo = [
        'category_id' => $request->category_id,
        'current_category_product_count' => count($currentCategoryProducts),
        'child_categories' => $childCategoryIds,
        'child_categories_count' => count($childCategoryIds),
        'child_products_count' => count($childProductIds),
        'total_products' => count($allCategoryProductIds)
    ];
    
    if (empty($allCategoryProductIds)) {
        return response()->json([
            'success' => true,
            'filters' => [],
            'products' => [],
            'brands' => [],
            'rating_filter' => [
                'filter_name' => 'Rating',
                'filter_type' => 'rating',
                'filter_values' => [5, 4, 3, 2, 1],
            ],
            'debug_info' => $debugInfo
        ]);
    }

    // Start with all category product IDs (including child categories)
    $filteredProductIds = collect($allCategoryProductIds);

    // Apply attribute filters if provided
    if ($request->has('filters') && is_array($request->filters)) {
        foreach ($request->filters as $filter) {
            if (!isset($filter['specification_name']) || !isset($filter['specification_value']) || empty($filter['specification_value'])) {
                continue;
            }

            $specName = $filter['specification_name'];
            $specValues = is_array($filter['specification_value']) ? $filter['specification_value'] : [$filter['specification_value']];

            // Find attribute ID based on name
            $attribute = Attribute::where('name', $specName)->first();
            if (!$attribute) {
                continue;
            }

            // Find product IDs that match this attribute and values
            $matchingProductIds = DB::table('product_attributes as pa')
                ->where('pa.attribute_id', $attribute->id)
                ->whereIn('pa.attribute_value', $specValues)
                ->whereIn('pa.product_id', $filteredProductIds)
                ->pluck('pa.product_id')
                ->unique();

            // Intersect with our running list of product IDs
            $filteredProductIds = $filteredProductIds->intersect($matchingProductIds);

            // If no products match these filters, return empty results early
            if ($filteredProductIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'filters' => [],
                    'products' => [],
                    'brands' => [],
                    'rating_filter' => [
                        'filter_name' => 'Rating',
                        'filter_type' => 'rating',
                        'filter_values' => [5, 4, 3, 2, 1],
                    ],
                    'debug_info' => $debugInfo
                ]);
            }
        }
    }

    // If a rating filter is applied, filter the already filtered product IDs
    if ($request->has('rating') && $request->rating) {
        $ratingFilteredIds = DB::table('ec_reviews')
            ->whereIn('product_id', $filteredProductIds)
            ->select('product_id')
            ->groupBy('product_id')
            ->havingRaw('ROUND(AVG(star)) = ?', [$request->rating])
            ->pluck('product_id');

        $filteredProductIds = $filteredProductIds->intersect($ratingFilteredIds);

        if ($filteredProductIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'filters' => [],
                'products' => [],
                'brands' => [],
                'rating_filter' => [
                    'filter_name' => 'Rating',
                    'filter_type' => 'rating',
                    'filter_values' => [5, 4, 3, 2, 1],
                ],
                'debug_info' => $debugInfo
            ]);
        }
    }

    // Fetching products based on filters
    $products = Product::whereIn('id', $filteredProductIds)
		->where('status', 'published')
        ->with(['currency', 'reviews', 'brand'])
        ->when($request->has('price_min') || $request->has('price_max'), function ($query) use ($request) {
            $min = $request->input('price_min', 0);
            $max = $request->input('price_max', PHP_INT_MAX);
            return $query->whereRaw("COALESCE(sale_price, price) BETWEEN ? AND ?", [$min, $max]);
        })
        ->when($request->has('brand_id') && $request->brand_id, function ($query) use ($request) {
            return $query->whereIn('brand_id', $request->brand_id);
        });

    // Apply sorting
    $sortBy = $request->input('sort_by', 'created_at');
    $sortByType = $request->input('sort_by_type', 'desc');
    if ($sortBy == 'price') {
        $products = $products->orderByRaw("COALESCE(sale_price, price) $sortByType");
    } else {
        $products = $products->orderBy($sortBy, $sortByType);
    }

    $paginatedProducts = $products->paginate($perPage);
    $modifiedProducts = $paginatedProducts->getCollection()->map(function ($product) {
        $product->currency_title = optional($product->currency)->title;
        $product->avg_rating = $product->reviews->count() > 0 ? round($product->reviews->avg('star')) : null;
        $product->brand_name = optional($product->brand)->name;

        $product->images = collect(is_array($product->images) ? $product->images : [])->map(function ($img) {
            return preg_match('/^(http|https):\/\//', $img) ? $img : asset('storage/' . $img);
        })->toArray();

        unset($product->currency, $product->reviews, $product->brand);
        return $product;
    });

    $paginatedProducts->setCollection($modifiedProducts);

    // Initialize filters array - will remain empty if subcategory doesn't exist
    $filters = [];
    
    // Get subcategory for this category
    $subCategory = DB::table('sub_categories')
        ->where('category_id', $request->category_id)
        ->first();
    
    $debugInfo['has_subcategory'] = $subCategory ? true : false;
    
    // Only process attribute filters if the subcategory exists
    if ($subCategory) {
        $attributeIdsField = null;
        $attributeIds = [];
        
        // Check which attribute ID field exists
        if (property_exists($subCategory, 'attributes_ids') || isset($subCategory->attributes_ids)) {
            $attributeIdsField = 'attributes_ids';
        } else if (property_exists($subCategory, 'attributes_jd') || isset($subCategory->attributes_jd)) {
            $attributeIdsField = 'attributes_jd';
        }
        
        $debugInfo['attribute_ids_field'] = $attributeIdsField;
        
        // Process attribute IDs if the field exists and has value
        if ($attributeIdsField && !empty($subCategory->$attributeIdsField)) {
            $attributeIdsValue = $subCategory->$attributeIdsField;
            
            // Parse attribute IDs based on data type
            if (is_string($attributeIdsValue)) {
                $attributeIds = json_decode($attributeIdsValue, true);
                $debugInfo['json_decode_error'] = json_last_error_msg();
                
                // If it's not valid JSON, try comma-separated format
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $attributeIds = explode(',', $attributeIdsValue);
                    $debugInfo['using_comma_separated'] = true;
                }
                // Special case: Check if we have an array containing a single string with comma-separated values
                else if (count($attributeIds) === 1 && is_string($attributeIds[0]) && strpos($attributeIds[0], ',') !== false) {
                    $attributeIds = explode(',', $attributeIds[0]);
                    $debugInfo['using_nested_comma_separated'] = true;
                }
            } else {
                $attributeIds = $attributeIdsValue;
            }
            
            // Ensure we have an array of integers
            $attributeIds = array_map('intval', (array)$attributeIds);
            $debugInfo['attribute_ids_parsed'] = $attributeIds;
            
            // Only proceed if we have valid attribute IDs
            if (!empty($attributeIds)) {
                // Get attribute filters for both parent and child category products
                $attributeValues = DB::table('product_attributes as pa')
                    ->join('attributes as at', 'at.id', '=', 'pa.attribute_id')
					->where('p.status', 'published')
                    ->whereIn('pa.product_id', $allCategoryProductIds)
                    ->whereIn('pa.attribute_id', $attributeIds)
                    ->select('at.name as attribute_name', 'pa.attribute_value', 'at.id as attribute_id')
                    ->get();
                    
                $debugInfo['attribute_values_count'] = $attributeValues->count();
                
                // If we have any attribute values
                if ($attributeValues->count() > 0) {
                    $attributeValues = $attributeValues->groupBy('attribute_name');
                    
                    // Process attribute filters
                    foreach ($attributeValues as $attributeName => $values) {
                        $uniqueValues = $values->pluck('attribute_value')->unique()->filter()->values();

                        if ($uniqueValues->every(fn($val) => is_numeric($val)) && $uniqueValues->count() > 2) {
                            $sorted = $uniqueValues->map(fn($val) => (float)$val)->sort()->values();
                            $chunkCount = min(5, ceil($sorted->count() / 2));
                            $chunkSize = ceil($sorted->count() / $chunkCount);

                            $ranges = $sorted->chunk($chunkSize)->map(function ($chunk) {
                                return [
                                    'min' => $chunk->first(),
                                    'max' => $chunk->last(),
                                ];
                            })->toArray();

                            $filters[] = [
                                'specification_name' => $attributeName,
                                'specification_type' => 'range',
                                'specification_value' => $ranges,
                            ];
                        } else {
                            $filters[] = [
                                'specification_name' => $attributeName,
                                'specification_type' => 'fixed',
                                'specification_value' => $uniqueValues->values(),
                            ];
                        }
                    }
                }
            }
        } else {
            $debugInfo['attributes_field_empty'] = true;
        }
    }

    // Get brands from all products (parent + child categories)
    $brandIds = Product::whereIn('id', $allCategoryProductIds)->where('status', 'published')->whereNotNull('brand_id')->pluck('brand_id')->unique();
    $brands = Brand::whereIn('id', $brandIds)->select('id', 'name')->get();

    $ratingFilter = [
        'filter_name' => 'Rating',
        'filter_type' => 'rating',  
        'filter_values' => [5, 4, 3, 2, 1],
    ];

    // Return the combined response with debug info
    return response()->json([
        'success' => true,
        'filters' => $filters,
        'products' => $paginatedProducts,
        'brands' => $brands,
        'rating_filter' => $ratingFilter,
        'debug_info' => $debugInfo
    ]);
}
// 	public function getSpecificationFilters(Request $request)
// {
//     // Existing validation code
//     $validator = Validator::make($request->all(), [
//         'category_id' => 'required|integer',
//         'filters' => 'nullable|array',
//         'price_min' => 'nullable|numeric|min:0',
//         'price_max' => 'nullable|numeric|min:0',
//         'price_order' => 'nullable|in:high_to_low,low_to_high',
//         'brand_id' => 'nullable|array',
//         'brand_id.*' => 'integer',
//         'rating' => 'nullable|numeric|min:1|max:5',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['success' => false, 'message' => $validator->errors()], 400);
//     }

//     $perPage = $request->get('per_page', 10);
//     $category = ProductCategory::find($request->category_id);
//     if (!$category) {
//         return response()->json(['success' => false, 'message' => 'Category does not exist.'], 400);
//     }

//     $categoryProductIds = $category->products->pluck('id')->all();
//     if (empty($categoryProductIds)) {
//         return response()->json([
//             'success' => true,
//             'filters' => [],
//             'products' => [],
//             'brands' => [],
//             'rating_filter' => [
//                 'filter_name' => 'Rating',
//                 'filter_type' => 'rating',
//                 'filter_values' => [5, 4, 3, 2, 1],
//             ],
//         ]);
//     }

//     // Start with all category product IDs
//     $filteredProductIds = collect($categoryProductIds);

//     // Apply attribute filters if provided
//     if ($request->has('filters') && is_array($request->filters)) {
//         foreach ($request->filters as $filter) {
//             if (!isset($filter['specification_name']) || !isset($filter['specification_value']) || empty($filter['specification_value'])) {
//                 continue;
//             }

//             $specName = $filter['specification_name'];
//             $specValues = is_array($filter['specification_value']) ? $filter['specification_value'] : [$filter['specification_value']];

//             // Find attribute ID based on name
//             $attribute = Attribute::where('name', $specName)->first();
//             if (!$attribute) {
//                 continue;
//             }

//             // Find product IDs that match this attribute and values
//             $matchingProductIds = DB::table('product_attributes as pa')
//                 ->where('pa.attribute_id', $attribute->id)
//                 ->whereIn('pa.attribute_value', $specValues)
//                 ->whereIn('pa.product_id', $filteredProductIds)
//                 ->pluck('pa.product_id')
//                 ->unique();

//             // Intersect with our running list of product IDs
//             $filteredProductIds = $filteredProductIds->intersect($matchingProductIds);

//             // If no products match these filters, return empty results early
//             if ($filteredProductIds->isEmpty()) {
//                 return response()->json([
//                     'success' => true,
//                     'filters' => [],
//                     'products' => [],
//                     'brands' => [],
//                     'rating_filter' => [
//                         'filter_name' => 'Rating',
//                         'filter_type' => 'rating',
//                         'filter_values' => [5, 4, 3, 2, 1],
//                     ],
//                 ]);
//             }
//         }
//     }

//     // If a rating filter is applied, filter the already filtered product IDs
//     if ($request->has('rating') && $request->rating) {
//         $ratingFilteredIds = DB::table('ec_reviews')
//             ->whereIn('product_id', $filteredProductIds)
//             ->select('product_id')
//             ->groupBy('product_id')
//             ->havingRaw('ROUND(AVG(star)) = ?', [$request->rating])
//             ->pluck('product_id');

//         $filteredProductIds = $filteredProductIds->intersect($ratingFilteredIds);

//         if ($filteredProductIds->isEmpty()) {
//             return response()->json([
//                 'success' => true,
//                 'filters' => [],
//                 'products' => [],
//                 'brands' => [],
//                 'rating_filter' => [
//                     'filter_name' => 'Rating',
//                     'filter_type' => 'rating',
//                     'filter_values' => [5, 4, 3, 2, 1],
//                 ],
//             ]);
//         }
//     }

//     // Fetching products based on filters
//     $products = Product::whereIn('id', $filteredProductIds)
//         ->with(['currency', 'reviews', 'brand'])
//         ->when($request->has('price_min') || $request->has('price_max'), function ($query) use ($request) {
//             $min = $request->input('price_min', 0);
//             $max = $request->input('price_max', PHP_INT_MAX);
//             return $query->whereRaw("COALESCE(sale_price, price) BETWEEN ? AND ?", [$min, $max]);
//         })
//         ->when($request->has('brand_id') && $request->brand_id, function ($query) use ($request) {
//             return $query->whereIn('brand_id', $request->brand_id);
//         });

//     // Apply sorting
//     $sortBy = $request->input('sort_by', 'created_at');
//     $sortByType = $request->input('sort_by_type', 'desc');
//     if ($sortBy == 'price') {
//         $products = $products->orderByRaw("COALESCE(sale_price, price) $sortByType");
//     } else {
//         $products = $products->orderBy($sortBy, $sortByType);
//     }

//     $paginatedProducts = $products->paginate($perPage);
//     $modifiedProducts = $paginatedProducts->getCollection()->map(function ($product) {
//         $product->currency_title = optional($product->currency)->title;
//         $product->avg_rating = $product->reviews->count() > 0 ? round($product->reviews->avg('star')) : null;
//         $product->brand_name = optional($product->brand)->name;

//         $product->images = collect(is_array($product->images) ? $product->images : [])->map(function ($img) {
//             return preg_match('/^(http|https):\/\//', $img) ? $img : asset('storage/' . $img);
//         })->toArray();

//         unset($product->currency, $product->reviews, $product->brand);
//         return $product;
//     });

//     $paginatedProducts->setCollection($modifiedProducts);

//     // Get all available attribute filters for this category
//     $attributeValues = DB::table('product_attributes as pa')
//         ->join('attributes as at', 'at.id', '=', 'pa.attribute_id')
//         ->whereIn('pa.product_id', $categoryProductIds)
//         ->select('at.name as attribute_name', 'pa.attribute_value', 'at.id as attribute_id')
//         ->get()
//         ->groupBy('attribute_name');

//     $filters = [];
//     foreach ($attributeValues as $attributeName => $values) {
//         $uniqueValues = $values->pluck('attribute_value')->unique()->filter()->values();

//         if ($uniqueValues->every(fn($val) => is_numeric($val)) && $uniqueValues->count() > 2) {
//             $sorted = $uniqueValues->map(fn($val) => (float)$val)->sort()->values();
//             $chunkCount = min(5, ceil($sorted->count() / 2));
//             $chunkSize = ceil($sorted->count() / $chunkCount);

//             $ranges = $sorted->chunk($chunkSize)->map(function ($chunk) {
//                 return [
//                     'min' => $chunk->first(),
//                     'max' => $chunk->last(),
//                 ];
//             })->toArray();

//             $filters[] = [
//                 'specification_name' => $attributeName,
//                 'specification_type' => 'range',
//                 'specification_value' => $ranges,
//             ];
//         } else {
//             $filters[] = [
//                 'specification_name' => $attributeName,
//                 'specification_type' => 'fixed',
//                 'specification_value' => $uniqueValues->values(),
//             ];
//         }
//     }

//     $brandIds = Product::whereIn('id', $categoryProductIds)->whereNotNull('brand_id')->pluck('brand_id')->unique();
//     $brands = Brand::whereIn('id', $brandIds)->select('id', 'name')->get();

//     $ratingFilter = [
//         'filter_name' => 'Rating',
//         'filter_type' => 'rating',
//         'filter_values' => [5, 4, 3, 2, 1],
//     ];

//     return response()->json([
//         'success' => true,
//         'filters' => $filters,
//         'products' => $paginatedProducts,
//         'brands' => $brands,
//         'rating_filter' => $ratingFilter,
//     ]);
// }
	
	
}