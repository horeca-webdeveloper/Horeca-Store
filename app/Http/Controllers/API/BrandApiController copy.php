<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Botble\Ecommerce\Models\Brand;

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
    public function getAllBrandProducts(Request $request)
    {
        $wishlistIds = $this->getWishlistProductIds();

        // Cache brands with product filtering
        $brands = Cache::remember('logged_in_brands', 60, function () use ($request) {
            return Brand::with([
                'products' => function ($query) use ($request) {
                    if ($request->has('search')) {
                        $query->where('name', 'like', '%' . $request->input('search') . '%');
                    }

                    if ($request->has('price_min')) {
                        $query->where('price', '>=', $request->input('price_min'));
                    }

                    if ($request->has('price_max')) {
                        $query->where('price', '<=', $request->input('price_max'));
                    }

                    if ($request->has('rating')) {
                        $query->whereHas('reviews', function ($q) use ($request) {
                            $q->selectRaw('AVG(star) as avg_rating')
                              ->groupBy('product_id')
                              ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
                        });
                    }
                }
            ])
            ->limit(20) // Limit number of brands/products fetched
            ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $brands->map(function ($brand) use ($wishlistIds) {
                return [
                    'brand_name' => $brand->name,
                    'products' => $brand->products->map(function ($product) use ($wishlistIds) {
                        return [
                                     "id" => $product->id,
                                    "name" => $product->name,
                                    "images" => $product->images, // Placeholder, replace with actual product images if needed
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
     * Optimized query logic for guest users.
     */
    public function getAllBrandGuestProducts(Request $request)
    {
        $brands = Cache::remember('guest_brands', 60, function () use ($request) {
            return Brand::with([
                'products' => function ($query) use ($request) {
                    if ($request->has('search')) {
                        $query->where('name', 'like', '%' . $request->input('search') . '%');
                    }

                    if ($request->has('price_min')) {
                        $query->where('price', '>=', $request->input('price_min'));
                    }

                    if ($request->has('price_max')) {
                        $query->where('price', '<=', $request->input('price_max'));
                    }

                    if ($request->has('rating')) {
                        $query->whereHas('reviews', function ($q) use ($request) {
                            $q->selectRaw('AVG(star) as avg_rating')
                              ->groupBy('product_id')
                              ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
                        });
                    }
                }
            ])
            ->limit(20) // Limit number of brands/products fetched
            ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $brands->map(function ($brand) {
                return [
                    'brand_name' => $brand->name,
                    'products' => $brand->products->map(function ($product) {
                        return [
                              "id" => $product->id,
                                   
                                     "id" => $product->id,
                                    "name" => $product->name,
                                    "images" => $product->images, // Placeholder, replace with actual product images if needed
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
