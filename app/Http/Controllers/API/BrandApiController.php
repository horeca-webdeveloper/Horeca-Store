<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Botble\Ecommerce\Models\Brand;
use Illuminate\Support\Str;


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
                        "name" => $product->name,
                        "images" => array_map(function ($image) use ($getImageUrl) {
                            return $getImageUrl($image);
                        }, $productImages),
                        "sku" => $product->sku ?? '',
                        "price" => $product->price,
                        "sale_price" => $product->sale_price ?? null,
                        "rating" => $product->reviews()->avg('star') ?? null,
                        "in_wishlist" => in_array($product->id, $wishlistIds),
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

    public function getAllBrandGuestProducts(Request $request)
    {
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
            'data' => $brands->map(function ($brand) use ($request) {
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
                    'products' => $products->map(function ($product) {
                        // Check product images and construct URLs
                        $getImageUrl = function ($imageName) {
                            if (Str::startsWith($imageName, ['http://', 'https://'])) {
                                return $imageName;
                            }
    
                            $imagePaths = [
                                public_path("storage/products/{$imageName}"),
                                public_path("storage/{$imageName}")
                            ];
    
                            foreach ($imagePaths as $path) {
                                if (file_exists($path)) {
                                    return asset('storage/' . str_replace(public_path('storage/'), '', $path));
                                }
                            }
    
                            return null; // Return null if image doesn't exist
                        };
    
                        $productImages = is_array($product->images) ? $product->images : ($product->images ? $product->images->toArray() : []);
    
                        return [
                            "id" => $product->id,
                            "name" => $product->name,
                            "images" => array_map(function ($image) use ($getImageUrl) {
                                return $getImageUrl($image);
                            }, $productImages),
                            "sku" => $product->sku ?? '',
                            "price" => $product->price,
                            "sale_price" => $product->sale_price ?? null,
                            "rating" => $product->reviews()->avg('star') ?? null,
                        ];
                    }),
                ];
            }),
        ]);
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
