<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Botble\Ecommerce\Models\Brand;
use Illuminate\Support\Str;
use Botble\Ecommerce\Models\Product;
use Illuminate\Http\JsonResponse;

class BrandApiController extends Controller
{
    /**
     * Fetch Wishlist Product IDs for logged-in and guest users.
     */
    private function getWishlistProductIds()
    {
        $userId = Auth::id();

        if ($userId) {
            return Cache::remember("wishlist_user_{$userId}", 60, function () use ($userId) {
                return DB::table('ec_wish_lists')
                    ->where('customer_id', $userId)
                    ->pluck('product_id')
                    ->toArray();
            });
        }

        return session()->get('guest_wishlist', []);
    }

    /**
     * Optimized query logic for logged-in users.
     */
//  public function getAllBrandProducts(Request $request)
// {
//     $wishlistIds = $this->getWishlistProductIds();

//     // Cache brands with product filtering
//     $brands = Cache::remember('logged_in_brands', 60, function () use ($request) {
//         return Brand::with([
//             'products' => function ($query) use ($request) {
//                 if ($request->has('search')) {
//                     $query->where('name', 'like', '%' . $request->input('search') . '%');
//                 }

//                 if ($request->has('price_min')) {
//                     $query->where('price', '>=', $request->input('price_min'));
//                 }

//                 if ($request->has('price_max')) {
//                     $query->where('price', '<=', $request->input('price_max'));
//                 }

//                 if ($request->has('rating')) {
//                     $query->whereHas('reviews', function ($q) use ($request) {
//                         $q->selectRaw('AVG(star) as avg_rating')
//                           ->groupBy('product_id')
//                           ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
//                     });
//                 }
                
//                     // Order products by a column in descending order, e.g., created_at
//     $query->orderBy('created_at', 'desc'); // Added this line to order the products
//             }
//         ])
//         ->limit(20) // Limit number of brands/products fetched
//         ->get();
//     });

//     return response()->json([
//         'success' => true,
//         'data' => $brands->map(function ($brand) use ($wishlistIds) {
//             return [
//                 'brand_name' => $brand->name,
//                 'products' => $brand->products->map(function ($product) use ($wishlistIds) {
                    
//                     // Function to get the full image URL
//                     $getImageUrl = function ($imageName) {
//                         $imagePaths = [
//                             public_path("storage/products/{$imageName}"),
//                             public_path("storage/{$imageName}")
//                         ];

//                         foreach ($imagePaths as $path) {
//                             if (file_exists($path)) {
//                                 return asset('storage/' . str_replace(public_path('storage/'), '', $path));
//                             }
//                         }

//                         return null; // Return null if image doesn't exist
//                     };

//                     // Check if 'images' is an array or a collection
//                     $productImages = is_array($product->images) ? $product->images : ($product->images ? $product->images->toArray() : []);

//                     return [
//                         "id" => $product->id,
//                         "name" => $product->name,
//                         "images" => array_map(function ($image) use ($getImageUrl) {
//                             return $getImageUrl($image); // Get full URL for each image
//                         }, $productImages),
//                         "sku" => $product->sku ?? '',
//                         "price" => $product->price,
//                         "sale_price" => $product->sale_price ?? null,
//                         "rating" => $product->reviews()->avg('star') ?? null,
//                         "in_wishlist" => in_array($product->id, $wishlistIds),
//                     ];
//                 }),
//             ];
//         }),
//     ]);
// }

// public function getAllBrandProducts(Request $request)
// {
//     $wishlistIds = $this->getWishlistProductIds();

//     // Fetch only the latest 5 brands with products
//     $brands = Brand::with(['products'])
//         ->has('products') // Only include brands with products
//         ->orderBy('created_at', 'desc') // Order by latest brands
//         ->take(5) // Limit to 5 brands
//         ->get();

//     return response()->json([
//         'success' => true,
//         'data' => $brands->map(function ($brand) use ($wishlistIds, $request) {
//             // Filter and limit products to 10 for each brand
//             $products = $brand->products()
//                 ->when($request->has('search'), function ($query) use ($request) {
//                     $query->where('name', 'like', '%' . $request->input('search') . '%');
//                 })
//                 ->when($request->has('price_min'), function ($query) use ($request) {
//                     $query->where('price', '>=', $request->input('price_min'));
//                 })
//                 ->when($request->has('price_max'), function ($query) use ($request) {
//                     $query->where('price', '<=', $request->input('price_max'));
//                 })
//                 ->when($request->has('rating'), function ($query) use ($request) {
//                     $query->whereHas('reviews', function ($q) use ($request) {
//                         $q->selectRaw('AVG(star) as avg_rating')
//                           ->groupBy('product_id')
//                           ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
//                     });
//                 })
//                 ->orderBy('created_at', 'desc') // Order products by latest
//                 ->take(10) // Limit to 10 products per brand
//                 ->get();

//             // Map brand data
//             return [
//                 'brand_name' => $brand->name,
//                 'products' => $products->map(function ($product) use ($wishlistIds) {
//                     // Check product images and construct URLs
//                     $getImageUrl = function ($imageName) {
//                         if (Str::startsWith($imageName, ['http://', 'https://'])) {
//                             return $imageName;
//                         }

//                         $imagePaths = [
//                             public_path("storage/products/{$imageName}"),
//                             public_path("storage/{$imageName}")
//                         ];

//                         foreach ($imagePaths as $path) {
//                             if (file_exists($path)) {
//                                 return asset('storage/' . str_replace(public_path('storage/'), '', $path));
//                             }
//                         }

//                         return null; // Return null if image doesn't exist
//                     };

//                     $productImages = is_array($product->images) ? $product->images : ($product->images ? $product->images->toArray() : []);

//                     return [
//                         "id" => $product->id,
//                         "name" => $product->name,
//                         "images" => array_map(function ($image) use ($getImageUrl) {
//                             return $getImageUrl($image);
//                         }, $productImages),
//                         "sku" => $product->sku ?? '',
//                         "price" => $product->price,
//                         "sale_price" => $product->sale_price ?? null,
//                         "rating" => $product->reviews()->avg('star') ?? null,
//                         "in_wishlist" => in_array($product->id, $wishlistIds),
//                     ];
//                 }),
//             ];
//         }),
//     ]);
// }
public function getAllHomeBrandProducts(Request $request)
{
    $wishlistIds = $this->getWishlistProductIds();

    // Fetch only the latest 5 brands with at least 10 products
    $brands = Brand::with(['products'])
        ->whereHas('products', function ($query) {
            $query->select('brand_id') // Select only the column needed for grouping
                ->groupBy('brand_id') // Group by the brand_id
                ->havingRaw('COUNT(*) >= 10'); // Ensure the brand has at least 10 products
        })
        ->orderBy('created_at', 'desc') // Order by latest brands
        ->take(5) // Limit to 5 brands
        ->get();

    return response()->json([
        'success' => true,
        'data' => $brands->map(function ($brand) use ($request, $wishlistIds) {
            // Filter and limit products to 10 for each brand
            $products = $brand->products()
                ->when($request->has('search'), function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->input('search') . '%');
                })
                ->when($request->has('price_min'), function ($query) use ($request) {
                    $query->where('price', '>=', $request->input('price_min'));
                })
                ->when($request->has('price_max'), function ($query) use ($request) {
                    $query->where('price', '<=', $request->input('price_max'));
                })
                ->when($request->has('rating'), function ($query) use ($request) {
                    $query->whereHas('reviews', function ($q) use ($request) {
                        $q->selectRaw('AVG(star) as avg_rating')
                            ->groupBy('product_id')
                            ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
                    });
                })
                ->orderBy('created_at', 'desc') // Order products by latest
                ->take(10) // Limit to 10 products per brand
                ->get();

            // Map brand data
            return [
                'brand_name' => $brand->name,
                'products' => $products->map(function ($product) use ($wishlistIds) {
                    // Improved logic to construct image URLs
                    $getImageUrl = function ($imageName) {
                        if (Str::startsWith($imageName, ['http://', 'https://'])) {
                            return $imageName; // Return the full URL if already valid
                        }

                        $paths = [
                            "storage/products/{$imageName}",
                            "storage/{$imageName}",
                        ];

                        foreach ($paths as $path) {
                            if (file_exists(public_path($path))) {
                                return asset($path);
                            }
                        }

                        return asset('images/default.png'); // Default image if none found
                    };

                    $productImages = is_array($product->images) ? $product->images : ($product->images ? $product->images->toArray() : []);

                    return [
                        "id" => $product->id,
                        // "name" => $product->name,
                        // "images" => array_map(function ($image) use ($getImageUrl) {
                        //     return $getImageUrl($image);
                        // }, $productImages),
                        // "sku" => $product->sku ?? '',
                        // "price" => $product->price,
                        // "sale_price" => $product->sale_price ?? null,
                        // "rating" => $product->reviews()->avg('star') ?? null,
                        // "in_wishlist" => in_array($product->id, $wishlistIds),
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'price' => $product->price,
                        'sale_price' => $product->sale_price,
                        'best_delivery_date' => $product->best_delivery_date,
                        'total_reviews' => $product->reviews->count(),
                        'avg_rating' => $product->reviews->count() > 0 ? $product->reviews->avg('star') : null,
                        'left_stock' => $product->left_stock ?? 0,
                        'currency' => $product->currency->title ?? 'USD',
                        'in_wishlist' => $product->in_wishlist ?? false,
                        'images' => collect($product->images)->map(function ($image) {
                            if (filter_var($image, FILTER_VALIDATE_URL)) {
                                return $image;
                            }
                            $baseUrl = (strpos($image, 'storage/products/') === 0) ? url('storage/products/') : url('storage/');
                            return $baseUrl . '/' . ltrim($image, '/');
                        })->toArray(),
                        'original_price' => $product->price,
                        'front_sale_price' => $product->price,
                        'best_price' => $product->price,
                    ];
                }),
            ];
        }),
    ]);
}



    /**
     * Optimized query logic for guest users dfds s.
     */
    // public function getAllBrandGuestProducts(Request $request)
    // {
    //     $brands = Cache::remember('guest_brands', 60, function () use ($request) {
    //         return Brand::with([
    //             'products' => function ($query) use ($request) {
    //                 if ($request->has('search')) {
    //                     $query->where('name', 'like', '%' . $request->input('search') . '%');
    //                 }

    //                 if ($request->has('price_min')) {
    //                     $query->where('price', '>=', $request->input('price_min'));
    //                 }

    //                 if ($request->has('price_max')) {
    //                     $query->where('price', '<=', $request->input('price_max'));
    //                 }

    //                 if ($request->has('rating')) {
    //                     $query->whereHas('reviews', function ($q) use ($request) {
    //                         $q->selectRaw('AVG(star) as avg_rating')
    //                           ->groupBy('product_id')
    //                           ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
    //                     });
    //                 }
    //                     // Order products by a column in descending order, e.g., created_at
    //      $query->orderBy('created_at', 'desc'); // Added this line to order the products
    //             }
    //         ])
    //         ->limit(20) // Limit number of brands/products fetched
    //         ->get();
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'data' => $brands->map(function ($brand) {
    //             return [
    //                 'brand_name' => $brand->name,
    //                 'products' => $brand->products->map(function ($product) {
    //                       // Function to get the full image URL
    //                 $getImageUrl = function ($imageName) {
    //                     $imagePaths = [
    //                         public_path("storage/products/{$imageName}"),
    //                         public_path("storage/{$imageName}")
    //                     ];

    //                     foreach ($imagePaths as $path) {
    //                         if (file_exists($path)) {
    //                             return asset('storage/' . str_replace(public_path('storage/'), '', $path));
    //                         }
    //                     }

    //                     return null; // Return null if image doesn't exist
    //                 };

    //                 // Check if 'images' is an array or a collection
    //                 $productImages = is_array($product->images) ? $product->images : ($product->images ? $product->images->toArray() : []);
                    
    //                     return [
    //                           "id" => $product->id,
                                   
    //                                  "id" => $product->id,
    //                                 "name" => $product->name,
    //                                  "images" => array_map(function ($image) use ($getImageUrl) {
    //                         return $getImageUrl($image); // Get full URL for each image
    //                     }, $productImages),
    //                                 "sku" => $product->sku ?? '',
    //                                 "price" => $product->price,
    //                                 "sale_price" => $product->sale_price ?? null,
                                  
    //                                 "rating" => $product->reviews()->avg('star') ?? null,
                                    
                            
    //                     ];
    //                 }),
    //             ];
    //         }),
    //     ]);
    //}

    // public function getAllBrandGuestProducts(Request $request)
    // {
    //     // Fetch only the latest 5 brands with at least 10 products
    //     $brands = Brand::with(['products'])
    //         ->whereHas('products', function ($query) {
    //             $query->select('brand_id') // Select only the column needed for grouping
    //                 ->groupBy('brand_id') // Group by the brand_id
    //                 ->havingRaw('COUNT(*) >= 10'); // Ensure the brand has at least 10 products
    //         })
    //         ->orderBy('created_at', 'desc') // Order by latest brands
    //         ->take(5) // Limit to 5 brands
    //         ->get();
    
    //     return response()->json([
    //         'success' => true,
    //         'data' => $brands->map(function ($brand) use ($request) {
    //             // Filter and limit products to 10 for each brand
    //             $products = $brand->products()
    //                 ->when($request->has('search'), function ($query) use ($request) {
    //                     $query->where('name', 'like', '%' . $request->input('search') . '%');
    //                 })
    //                 ->when($request->has('price_min'), function ($query) use ($request) {
    //                     $query->where('price', '>=', $request->input('price_min'));
    //                 })
    //                 ->when($request->has('price_max'), function ($query) use ($request) {
    //                     $query->where('price', '<=', $request->input('price_max'));
    //                 })
    //                 ->when($request->has('rating'), function ($query) use ($request) {
    //                     $query->whereHas('reviews', function ($q) use ($request) {
    //                         $q->selectRaw('AVG(star) as avg_rating')
    //                             ->groupBy('product_id')
    //                             ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
    //                     });
    //                 })
    //                 ->orderBy('created_at', 'desc') // Order products by latest
    //                 ->take(10) // Limit to 10 products per brand
    //                 ->get();
    
    //             // Map brand data
    //             return [
    //                 'brand_name' => $brand->name,
    //                 'products' => $products->map(function ($product) {
    //                     // Check product images and construct URLs
    //                     $getImageUrl = function ($imageName) {
    //                         if (Str::startsWith($imageName, ['http://', 'https://'])) {
    //                             return $imageName;
    //                         }
    
    //                         $imagePaths = [
    //                             public_path("storage/products/{$imageName}"),
    //                             public_path("storage/{$imageName}")
    //                         ];
    
    //                         foreach ($imagePaths as $path) {
    //                             if (file_exists($path)) {
    //                                 return asset('storage/' . str_replace(public_path('storage/'), '', $path));
    //                             }
    //                         }
    
    //                         return null; // Return null if image doesn't exist
    //                     };
    
    //                     $productImages = is_array($product->images) ? $product->images : ($product->images ? $product->images->toArray() : []);
    
    //                     return [
    //                         "id" => $product->id,
    //                         "name" => $product->name,
    //                         "images" => array_map(function ($image) use ($getImageUrl) {
    //                             return $getImageUrl($image);
    //                         }, $productImages),
    //                         "sku" => $product->sku ?? '',
    //                         "price" => $product->price,
    //                         "original_price" => $product->price,
    //                         "sale_price" => $product->sale_price ?? null,
    //                         "rating" => $product->reviews()->avg('star') ?? null,
    //                     ];
    //                 }),
    //             ];
    //         }),
    //     ]);
    // }
    public function getAllBrandGuestProducts(Request $request)
{
    // Subquery for best price and delivery days by SKU
    $subQuery = Product::select('sku')
        ->selectRaw('MIN(price) as best_price')
        ->selectRaw('MIN(delivery_days) as best_delivery_date')
        ->groupBy('sku');

    // Fetch only the latest 5 brands with at least 10 products
    $brands = Brand::with(['products'])
        ->whereHas('products', function ($query) {
            $query->select('brand_id')
                ->groupBy('brand_id')
                ->havingRaw('COUNT(*) >= 10');
        })
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $brands->map(function ($brand) use ($request, $subQuery) {
            // Filter and limit products to 10 for each brand
            $products = $brand->products()
                ->when($request->has('search'), function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->input('search') . '%');
                })
                ->when($request->has('price_min'), function ($query) use ($request) {
                    $query->where('price', '>=', $request->input('price_min'));
                })
                ->when($request->has('price_max'), function ($query) use ($request) {
                    $query->where('price', '<=', $request->input('price_max'));
                })
                ->when($request->has('rating'), function ($query) use ($request) {
                    $query->whereHas('reviews', function ($q) use ($request) {
                        $q->selectRaw('AVG(star) as avg_rating')
                            ->groupBy('product_id')
                            ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
                    });
                })
                ->take(10)
                ->pluck('id'); // Only get product IDs

            // Fetch product details with joined best_price and eager load
            $productDetails = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                    $join->on('ec_products.sku', '=', 'best_products.sku')
                         ->whereColumn('ec_products.price', 'best_products.best_price');
                })
                ->whereIn('ec_products.id', $products)
                ->with(['reviews', 'currency'])
                ->get()
                ->keyBy('id');

            return [
                'brand_name' => $brand->name,
                'products' => $productDetails->map(function ($details) {
                    $totalReviews = $details->reviews->count();
                    $avgRating = $totalReviews > 0 ? $details->reviews->avg('star') : null;
                    $leftStock = ($details->quantity ?? 0) - ($details->units_sold ?? 0);
                    $currencyTitle = $details->currency->title ?? $details->price;

                    $imageUrls = collect($details->images)->map(fn($image) =>
                        Str::startsWith($image, ['http://', 'https://']) ? $image : asset('storage/' . ltrim($image, '/'))
                    );

                    return [
                        'id' => $details->id,
                        'name' => $details->name,
                        'sku' => $details->sku,
                        'price' => $details->best_price ?? $details->price,
                        'original_price' => $details->price,
                        'sale_price' => $details->sale_price,
                        'best_delivery_date' => $details->best_delivery_date,
                        'total_reviews' => $totalReviews,
                        'avg_rating' => $avgRating,
                        'left_stock' => $leftStock,
                        'currency' => $currencyTitle,
                        'images' => $imageUrls,
                        'front_sale_price' => $details->price,
                        'best_price' => $details->best_price ?? $details->price,
                    ];
                })->values(),
            ];
        }),
    ]);
}


    
    public function brandsByCategory($id): JsonResponse
    {
        $brandIds = Product::whereHas('categories', function ($query) use ($id) {
            $query->where('ec_product_category_product.category_id', $id);
        })->pluck('brand_id')->unique()->filter();

        if ($brandIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No brands found for this category.',
                'data' => []
            ], 404);
        }

        $brands = Brand::whereIn('id', $brandIds)
            ->where('status', 'published')
            ->select('id', 'name', 'logo')
            ->get()
            ->map(function ($brand) {
                $brand->logo = $brand->logo ? asset( $brand->logo) : null;
                return $brand;
            });

        return response()->json([
            'success' => true,
            'message' => 'Brands retrieved successfully.',
            'data' => $brands
        ]);
    }


    public function getCategories($id)
	 {
		 $brand = Brand::with(['products.categories:id,name,image'])->findOrFail($id);

		 // Flatten and get unique categories, only with id and name
		 $categories = $brand->products
			 ->flatMap(function ($product) {
				 return $product->categories->map(function ($category) {
					 return [
						 'id' => $category->id,
						 'name' => $category->name,
                         'image' => asset('storage/' . $category->image), // full URL
					 ];
				 });
			 })
			 ->unique('id')
			 ->values();

		 return response()->json([
			 'sucess' => 'true',
			 'brand_id' => $id,
			 'categories' => $categories
		 ]);
	 }

    
    
    

     public function getProductsByBrandAndCategory(Request $request, $brandId, $categoryId = null)
     {
         try {
             $brand = Brand::with(['products.categories'])->findOrFail($brandId);
     
             // Get all brand products or filter by category
             $filteredProducts = is_null($categoryId)
                 ? $brand->products
                 : $brand->products->filter(function ($product) use ($categoryId) {
                     return $product->categories->contains('id', $categoryId);
                 })->values();
     
             if ($filteredProducts->isEmpty()) {
                 return response()->json([
                     'success' => true,
                     'message' => 'No products found for this brand' . ($categoryId ? ' and category' : ''),
                     'data' => [],
                     'pagination' => $this->emptyPagination(),
                 ]);
             }
     
             $productIds = $filteredProducts->pluck('id')->toArray();
     
             $productsWithRelations = Product::whereIn('id', $productIds)
                 ->with([
                     'reviews:id,product_id,star',
                     'currency',
                     'specifications',
                 ])
                 ->get()
                 ->keyBy('id');
     
             $perPage = 50;
             $page = max(1, (int) $request->input('page', 1));
             $total = count($productIds);
             $offset = ($page - 1) * $perPage;
             $paginatedProducts = $filteredProducts->slice($offset, $perPage);
     
             $pagination = $this->buildPagination($page, $perPage, $total);
     
             $transformedProducts = $paginatedProducts->map(function ($product) use ($productsWithRelations) {
                 $productWithRelations = $productsWithRelations->get($product->id) ?? $product;
     
                 $images = $this->normalizeMediaUrls($product->images);
                 $videos = $this->normalizeMediaUrls($product->video_path);
     
                 $totalReviews = $productWithRelations->reviews ? $productWithRelations->reviews->count() : 0;
                 $avgRating = $totalReviews > 0 ? $productWithRelations->reviews->avg('star') : null;
     
                 $quantity = $product->quantity ?? 0;
                 $unitsSold = $product->units_sold ?? 0;
                 $leftStock = $quantity - $unitsSold;
     
                 return [
                     'id' => $product->id,
                     'name' => $product->name,
                     'images' => $images,
                     'video_url' => $product->video_url,
                     'video_path' => $videos,
                     'sku' => $product->sku,
                     'original_price' => $product->price,
                     'front_sale_price' => $product->price,
                     'sale_price' => $product->sale_price,
                     'price' => $product->price,
                     'start_date' => $product->start_date,
                     'end_date' => $product->end_date,
                     'warranty_information' => $product->warranty_information,
                     'currency' => $productWithRelations->currency?->title,
                     'total_reviews' => $totalReviews,
                     'avg_rating' => $avgRating,
                     'best_price' => $product->sale_price ?? $product->price,
                     'best_delivery_date' => null,
                     'leftStock' => $leftStock,
                     'currency_title' => $productWithRelations->currency
                         ? ($productWithRelations->currency->is_prefix_symbol
                             ? $productWithRelations->currency->title
                             : ($product->price . ' ' . $productWithRelations->currency->title))
                         : $product->price,
                 ];
             });
     
             return response()->json([
                 'success' => true,
                 'data' => $transformedProducts->values(),
                 'pagination' => $pagination,
                 'message' => 'Products retrieved successfully',
             ]);
         } catch (\Exception $e) {
             Log::error('Error in getProductsByBrandAndCategory: ' . $e->getMessage());
             return response()->json([
                 'success' => false,
                 'message' => 'An error occurred while fetching products',
                 'error' => $e->getMessage(),
             ], 500);
         }
     }
     
protected function emptyPagination()
{
    return [
        'total' => 0,
        'per_page' => 0,
        'current_page' => 1,
        'last_page' => 1,
    ];
}

protected function buildPagination($page, $perPage, $total)
{
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'last_page' => ceil($total / $perPage),
    ];
}

protected function normalizeMediaUrls($media)
{
    if (is_array($media)) {
        return array_map(fn ($url) => url($url), $media);
    }
    return $media ? url($media) : null;
}



    // public function getAllBrandsAlphabetically(): JsonResponse
    // {
    //     $brands = Brand::where('status', 'published')
    //         ->select('id', 'name', 'logo')
    //         ->orderBy('name') // ensures alphabetical order
    //         ->get()
    //         ->map(function ($brand) {
    //             $brand->logo = $brand->logo ? asset($brand->logo) : null;
    //             return $brand;
    //         });
    
    //     $grouped = $brands->groupBy(function ($brand) {
    //         return strtoupper(substr($brand->name, 0, 1)); // group by first letter
    //     })->sortKeys(); // sort alphabetically by keys (A, B, C...)
    
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Brands grouped alphabetically.',
    //         'data' => $grouped
    //     ]);
    // }
    
public function getAllBrandsAlphabetically(Request $request): JsonResponse
{
    $letter = strtoupper($request->query('letter')); // e.g. ?letter=B

    $brandsQuery = Brand::where('status', 'published')
        ->select('id', 'name', 'logo')
        ->orderBy('name');

    if ($letter) {
        $brandsQuery->where('name', 'LIKE', $letter . '%');
    }

    $brands = $brandsQuery->get()->map(function ($brand) {
        $brand->logo = $brand->logo ? asset($brand->logo) : null;
        return $brand;
    });

    if ($letter) {
        // Return filtered brands only
        return response()->json([
            'success' => true,
            'message' => "Brands starting with letter '$letter'.",
            'data' => $brands
        ]);
    } else {
        // Return grouped by A-Z
        $grouped = $brands->groupBy(function ($brand) {
            return strtoupper(substr($brand->name, 0, 1));
        })->sortKeys();

        return response()->json([
            'success' => true,
            'message' => 'Brands grouped alphabetically.',
            'data' => $grouped
        ]);
    }
}




    
}

// namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Botble\Base\Events\BeforeEditContentEvent;
// use Botble\Base\Events\CreatedContentEvent;
// use Botble\Base\Facades\Assets;
// use Botble\Base\Supports\Breadcrumb;
// use Botble\Ecommerce\Enums\ProductTypeEnum;
// use Botble\Ecommerce\Facades\EcommerceHelper;
// use Botble\Ecommerce\Forms\ProductForm;
// use Botble\Ecommerce\Http\Requests\ProductRequest;
// use Botble\Ecommerce\Models\GroupedProduct;
// use Botble\Ecommerce\Models\Product;
// use Botble\Ecommerce\Models\Brand;
// use Botble\Ecommerce\Models\ProductVariation;
// use Botble\Ecommerce\Models\ProductVariationItem;
// use Botble\Ecommerce\Services\Products\DuplicateProductService;
// use Botble\Ecommerce\Services\Products\StoreAttributesOfProductService;
// use Botble\Ecommerce\Services\Products\StoreProductService;
// use Botble\Ecommerce\Services\StoreProductTagService;
// use Botble\Ecommerce\Tables\ProductTable;
// use Botble\Ecommerce\Tables\ProductVariationTable;
// use Botble\Ecommerce\Traits\ProductActionsTrait;
// use Botble\Ecommerce\Models\Review;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB; // Add this line
// class BrandApiController extends Controller
// {

// public function getAllBrandProducts(Request $request)
// {
    
//      // Get the logged-in user's ID
//     $userId = Auth::id();
//     $isUserLoggedIn = $userId !== null; // Check if the user is logged in

//     // Initialize an empty array to store product IDs in the wishlist
//     $wishlistProductIds = [];
    
//     // Fetch wishlist product IDs if the user is logged in
//     if ($isUserLoggedIn) {
//         $wishlistProductIds = DB::table('ec_wish_lists')
//             ->where('customer_id', $userId)
//             ->pluck('product_id')
//             ->map(function($id) {
//                 return (int) $id; // Ensure all IDs are integers
//             })
//             ->toArray(); // Get all product IDs in the user's wishlist
//     } else {
//         // Handle guest wishlist (using session, adjust as needed)
//         $wishlistProductIds = session()->get('guest_wishlist', []); // Example for guest wishlist
//     }
    
//     // Fetch all brands
//     $brands = Brand::with(['products' => function($query) use ($request) {
//         // Apply filters if necessary, similar to the getAllProducts method
//         if ($request->has('search')) {
//             $query->where('name', 'like', '%' . $request->input('search') . '%');
//         }
//         if ($request->has('price_min')) {
//             $query->where('price', '>=', $request->input('price_min'));
//         }
//         if ($request->has('price_max')) {
//             $query->where('price', '<=', $request->input('price_max'));
//         }
//         if ($request->has('rating')) {
//             $rating = $request->input('rating');
//             $query->whereHas('reviews', function($q) use ($rating) {
//                 $q->selectRaw('AVG(star) as avg_rating')
//                   ->groupBy('product_id')
//                   ->havingRaw('AVG(star) >= ?', [$rating]);
//             });
//         }
//         // Additional filters can be applied as needed
//     }])->get();


//     // Return the result in a JSON response
//     return response()->json([
//         'success' => true,
//         'data' => $brands->map(function ($brand) use ($wishlistProductIds) {
//             return [
//                 'brand_name' => $brand->name,
//                 'products' => $brand->products->map(function ($product) use ($wishlistProductIds) {
//                     $productArray = $product->toArray();

//                     // Add average rating to the product array
//                     $productArray['rating'] = $product->reviews()->avg('star'); // Average rating

//                     // Add 'is_wishlist' flag to indicate if the product is in the wishlist
//                     $productArray['in_wishlist'] = in_array($product->id, $wishlistProductIds);

//                     // Return the complete product array
//                     return $productArray;
//                 }),
//             ];
//         }),
//     ]);
// }

// public function getAllBrandGuestProducts(Request $request)
// {
    
//     // Fetch all brands
//     $brands = Brand::with(['products' => function($query) use ($request) {
//         // Apply filters if necessary, similar to the getAllProducts method
//         if ($request->has('search')) {
//             $query->where('name', 'like', '%' . $request->input('search') . '%');
//         }
//         if ($request->has('price_min')) {
//             $query->where('price', '>=', $request->input('price_min'));
//         }
//         if ($request->has('price_max')) {
//             $query->where('price', '<=', $request->input('price_max'));
//         }
//         if ($request->has('rating')) {
//             $rating = $request->input('rating');
//             $query->whereHas('reviews', function($q) use ($rating) {
//                 $q->selectRaw('AVG(star) as avg_rating')
//                   ->groupBy('product_id')
//                   ->havingRaw('AVG(star) >= ?', [$rating]);
//             });
//         }
//         // Additional filters can be applied as needed
//     }])->get();

//     // Return the result in a JSON response
//     return response()->json([
//         'success' => true,
//         'data' => $brands->map(function ($brand) {
//             return [
//                 'brand_name' => $brand->name,
//                 'products' => $brand->products->map(function ($product) {
//                     $productArray = $product->toArray();

//                     // Add average rating to the product array
//                     $productArray['rating'] = $product->reviews()->avg('star'); // Average rating

//                     // Return the complete product array
//                     return $productArray;
//                 }),
//             ];
//         }),
//     ]);
// }




// }
