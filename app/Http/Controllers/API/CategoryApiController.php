<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Ecommerce\Models\ProductCategory; // Ensure you import the Category model
use Botble\Ecommerce\Models\Product;
use Illuminate\Support\Facades\Auth;
use Botble\Ecommerce\Models\Currency;
use Illuminate\Support\Facades\DB;
class CategoryApiController extends Controller
{
    // public function getAllFeaturedProductsByCategory(Request $request)
    // {
    //     // Fetch all categories with their featured products
    //     $categories = ProductCategory::with(['products' => function($query) use ($request) {
    //         // Filter only products that are featured
    //         $query->where('is_featured', 1);
            
    //         // Apply additional filters if necessary
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
    //         'data' => $categories->map(function ($category) {
    //             return [
    //                 'category_name' => $category->name,
    //                 'featured_products' => $category->products->map(function ($product) {
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
    
//   public function getAllFeaturedProductsByCategory(Request $request)
// {
//     // Fetch all categories with their featured products
//     $categories = ProductCategory::with(['products' => function($query) use ($request) {
//         $query->where('is_featured', 1);

//         // Apply filters
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
//                 $q->havingRaw('AVG(star) >= ?', [$rating]);
//             });
//         }
//     }])->get();

//     // Limit featured products to 10 per category
//     $categories = $categories->map(function ($category) {
//         return [
//             'category_name' => $category->name,
//             'featured_products' => $category->products->take(10)->map(function ($product) {
//                 $productArray = $product->toArray();
//                 $productArray['rating'] = $product->reviews()->avg('star');
//                 return $productArray;
//             }),
//         ];
//     });

//     return response()->json([
//         'success' => true,
//         'data' => $categories,
//     ]);
// }


// public function getAllFeaturedProductsByCategory(Request $request)
// {
//     // Fetch only the first five featured categories with their featured products
//     $categories = ProductCategory::with(['products' => function($query) use ($request) {
//         $query->where('is_featured', 1);
        
//         // Temporarily remove filters to see available categories
//         /*
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
//                 $q->havingRaw('AVG(star) >= ?', [$rating]);
//             });
//         }
//         */
//     }])
//     ->where('is_featured', 1)
//     ->take(5)
//     ->get();

//     // Limit featured products to 10 per category
//     $categories = $categories->map(function ($category) {
//         return [
//             'category_name' => $category->name,
//             'featured_products' => $category->products->take(10)->map(function ($product) {
//                 $productArray = $product->toArray();
//                 $productArray['rating'] = $product->reviews()->avg('star');
//                 return $productArray;
//             }),
//         ];
//     });

//     return response()->json([
//         'success' => true,
//         'data' => $categories,
//     ]);
// }

// public function getAllFeaturedProductsByCategory(Request $request)
// {
//     // Fetch only the first five categories that have featured products
//     $categories = ProductCategory::whereHas('products', function($query) {
//         $query->where('is_featured', 1); // Ensure there are featured products
//     })
//     ->with(['products' => function($query) {
//         $query->where('is_featured', 1); // Only get featured products
//     }])
//     ->take(5) // Limit to 5 categories
//     ->get();

//     // Map the categories to include featured products
//     $categories = $categories->map(function ($category) {
//         return [
//             'category_name' => $category->name,
//             'featured_products' => $category->products->take(10)->map(function ($product) {
//                 return $product; // Return all product info
//             }),
//         ];
//     });

//     return response()->json([
//         'success' => true,
//         'data' => $categories,
//     ]);
// }

public function getAllFeaturedProductsByCategory(Request $request)
{
    // Get the logged-in user's ID
    $userId = Auth::id();
    $isUserLoggedIn = $userId !== null; // Check if the user is logged in

    // Initialize an empty array to store product IDs in the wishlist
    $wishlistProductIds = [];
    
    // Fetch wishlist product IDs if the user is logged in
    if ($isUserLoggedIn) {
        $wishlistProductIds = DB::table('ec_wish_lists')
            ->where('customer_id', $userId)
            ->pluck('product_id')
            ->map(function($id) {
                return (int) $id; // Ensure all IDs are integers
            })
            ->toArray(); // Get all product IDs in the user's wishlist
    } else {
        // Handle guest wishlist (using session, adjust as needed)
        $wishlistProductIds = session()->get('guest_wishlist', []); // Example for guest wishlist
    }

    // Fetch only the first five categories that have featured products
    $categories = ProductCategory::whereHas('products', function($query) {
        $query->where('is_featured', 1)
        ->where('status', 'published'); // Ensure there are featured products
    })
    ->with(['products' => function($query) {
        $query->where('is_featured', 1)
        ->where('status', 'published')
        ; // Only get featured products
    }])
    ->take(5) // Limit to 5 categories
    ->get();

    // Prepare a subquery for best price and delivery date
    $subQuery = Product::select('sku')
        ->selectRaw('MIN(price) as best_price')
        ->selectRaw('MIN(delivery_days) as best_delivery_date')
        ->groupBy('sku');

    // Map the categories to include featured products with additional info
    $categories = $categories->map(function ($category) use ($subQuery, $wishlistProductIds) {
        return [
            'category_name' => $category->name,
            'featured_products' => $category->products->take(10)->map(function ($product) use ($subQuery, $wishlistProductIds) {
                // Join with the subquery to get best price and delivery date
                $productDetails = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                    $join->on('ec_products.sku', '=', 'best_products.sku')
                         ->whereColumn('ec_products.price', 'best_products.best_price');
                })
                ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
                ->with('reviews', 'currency')
                ->where('ec_products.id', $product->id) // Only get the current product
                ->first(); // Fetch the product details

                // Count total reviews and calculate average rating
                $totalReviews = $productDetails->reviews->count();
                $avgRating = $totalReviews > 0 ? $productDetails->reviews->avg('star') : null;

                // Calculate left stock
                $quantity = $productDetails->quantity ?? 0;
                $unitsSold = $productDetails->units_sold ?? 0;
                $leftStock = $quantity - $unitsSold;

                // Add currency symbol
                $currencyTitle = $productDetails->currency ? $productDetails->currency->title : $productDetails->price; // Fallback if no currency found

                // Check if the product is in the wishlist
                $isInWishlist = in_array($productDetails->id, $wishlistProductIds);

                // Get the images URLs by prepending the correct base URL
                $imageUrls = collect($productDetails->images)->map(function ($image) {
                    if (strpos($image, 'storage/products/') === 0) {
                        // Image is inside storage/products folder
                        return asset('storage/products/' . $image);
                    } elseif (strpos($image, 'storage/') === 0) {
                        // Image is inside storage folder but not in the 'products' subfolder
                        return asset('storage/' . $image);
                    } else {
                        // Image is not inside storage, assume it's in public
                        return asset('storage/' . $image);  // this should be your fallback logic if images are in public
                    }
                });

                // Append the values to the product
                return array_merge($productDetails->toArray(), [
                    'total_reviews' => $totalReviews,
                    'avg_rating' => $avgRating,
                    'leftStock' => $leftStock,
                    'currency' => $currencyTitle,
                    'in_wishlist' => $isInWishlist, // Add wishlist status
                    'images' => $imageUrls, // Add the full image URLs here
                ]);
            }),
        ];
    });

    // Return the result in a JSON response
    return response()->json([
        'success' => true,
        'data' => $categories,
    ]);
}
// public function getAllFeaturedProductsByCategory(Request $request)
// {
//     // Get the logged-in user's ID
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

//     // Fetch only the first five categories that have featured products
//     $categories = ProductCategory::whereHas('products', function($query) {
//         $query->where('is_featured', 1) // Ensure there are featured products
//               ->where('ec_products.status', 'published') // Only published products
//               ->orderBy('created_at', 'desc'); // Order by creation date
//     })
//     ->with(['products' => function($query) {
//         $query->where('is_featured', 1)
//               ->where('ec_products.status', 'published')
//               ->orderBy('created_at', 'desc'); // Match published and ordered products
//     }])
//     ->take(5) // Limit to 5 categories
//     ->get();

//     // Prepare a subquery for best price and delivery date
//     $subQuery = Product::select('sku')
//         ->selectRaw('MIN(price) as best_price')
//         ->selectRaw('MIN(delivery_days) as best_delivery_date')
//         ->groupBy('sku');

//     // Map the categories to include featured products with additional info
//     $categories = $categories->map(function ($category) use ($subQuery, $wishlistProductIds) {
//         return [
//             'category_name' => $category->name,
//             'featured_products' => $category->products->take(10)->map(function ($product) use ($subQuery, $wishlistProductIds) {
//                 // Join with the subquery to get best price and delivery date
//                 $productDetails = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
//                     $join->on('ec_products.sku', '=', 'best_products.sku')
//                          ->whereColumn('ec_products.price', 'best_products.best_price');
//                 })
//                 ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
//                 ->with('reviews', 'currency')
//                 ->where('ec_products.id', $product->id) // Only get the current product
//                 ->first(); // Fetch the product details

//                 // Count total reviews and calculate average rating
//                 $totalReviews = $productDetails->reviews->count();
//                 $avgRating = $totalReviews > 0 ? $productDetails->reviews->avg('star') : null;

//                 // Calculate left stock
//                 $quantity = $productDetails->quantity ?? 0;
//                 $unitsSold = $productDetails->units_sold ?? 0;
//                 $leftStock = $quantity - $unitsSold;

//                 // Add currency symbol
//                 $currencyTitle = $productDetails->currency ? $productDetails->currency->title : $productDetails->price; // Fallback if no currency found

//                 // Check if the product is in the wishlist
//                 $isInWishlist = in_array($productDetails->id, $wishlistProductIds);

//                 // Get the images URLs by prepending the correct base URL
//                 $imageUrls = collect($productDetails->images)->map(function ($image) {
//                     // If the image is already a full URL, return it directly
//                     if (filter_var($image, FILTER_VALIDATE_URL)) {
//                         return $image;
//                     }

//                     // Dynamically resolve image paths
//                     $basePaths = [
//                         'storage/products/', // First, check 'products/' subfolder
//                         'storage/',          // Then, check the general 'storage/' folder
//                     ];

//                     // Loop through paths and check if the file exists
//                     foreach ($basePaths as $basePath) {
//                         $fullPath = asset($basePath . $image);
//                         if (file_exists(public_path($basePath . $image))) {
//                             return $fullPath; // Return the first valid path
//                         }
//                     }

//                     // Default: Return null or a fallback image if no valid path found
//                     return null; // Or a default placeholder image
//                 });

//                 // Append the values to the product
//                 return array_merge($productDetails->toArray(), [
//                     'total_reviews' => $totalReviews,
//                     'avg_rating' => $avgRating,
//                     'leftStock' => $leftStock,
//                     'currency' => $currencyTitle,
//                     'in_wishlist' => $isInWishlist, // Add wishlist status
//                     'images' => $imageUrls, // Add the full image URLs here
//                 ]);
//             })->toArray(),
//         ];
//     });

//     // Return the result in a JSON response
//     return response()->json([
//         'success' => true,
//         'data' => $categories,
//     ]);
// }




// public function getAllGuestFeaturedProductsByCategory(Request $request)
// {
//     // Fetch only the first five categories that have featured products
//     $categories = ProductCategory::whereHas('products', function($query) {
//         $query->where('is_featured', 1); // Ensure there are featured products
//     })
//     ->with(['products' => function($query) {
//         $query->where('is_featured', 1); // Only get featured products
//     }])
//     ->take(5) // Limit to 5 categories
//     ->get();

//     // Prepare a subquery for best price and delivery date
//     $subQuery = Product::select('sku')
//         ->selectRaw('MIN(price) as best_price')
//         ->selectRaw('MIN(delivery_days) as best_delivery_date')
//         ->groupBy('sku');

//     // Map the categories to include featured products with additional info
//     $categories = $categories->map(function ($category) use ($subQuery) {
//         return [
//             'category_name' => $category->name,
//             'featured_products' => $category->products->take(10)->map(function ($product) use ($subQuery) {
//                 // Join with the subquery to get best price and delivery date
//                 $productDetails = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
//                     $join->on('ec_products.sku', '=', 'best_products.sku')
//                          ->whereColumn('ec_products.price', 'best_products.best_price');
//                 })
//                 ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
//                 ->with('reviews', 'currency')
//                 ->where('ec_products.id', $product->id) // Only get the current product
//                 ->first(); // Fetch the product details

//                 // Count total reviews and calculate average rating
//                 $totalReviews = $productDetails->reviews->count();
//                 $avgRating = $totalReviews > 0 ? $productDetails->reviews->avg('star') : null;

//                 // Calculate left stock
//                 $quantity = $productDetails->quantity ?? 0;
//                 $unitsSold = $productDetails->units_sold ?? 0;
//                 $leftStock = $quantity - $unitsSold;

//                 // Add currency symbol
//                 $currencyTitle = $productDetails->currency ? $productDetails->currency->title : $productDetails->price; // Fallback if no currency found

//                 // Get the images URLs by prepending the correct base URL
//                 $imageUrls = collect($productDetails->images)->map(function ($image) {
//                     if (strpos($image, 'storage/products/') === 0) {
//                         // Image is inside storage/products folder
//                         return asset('storage/products/' . $image);
//                     } elseif (strpos($image, 'storage/') === 0) {
//                         // Image is inside storage folder but not in the 'products' subfolder
//                         return asset('storage/' . $image);
//                     } else {
//                         // Image is not inside storage, assume it's in public
//                         return asset('storage/' . $image);  // This assumes fallback logic if images are in public
//                     }
//                 });

//                 // Return product data with additional info
//                 return array_merge($productDetails->toArray(), [
//                     'total_reviews' => $totalReviews,
//                     'avg_rating' => $avgRating,
//                     'leftStock' => $leftStock,
//                     'currency' => $currencyTitle,
//                     'images' => $imageUrls, // Add the full image URLs here
//                 ]);
//             }),
//         ];
//     });

//     return response()->json([
//         'success' => true,
//         'data' => $categories,
//     ]);
// }

public function getAllGuestFeaturedProductsByCategory(Request $request)
{
    // Fetch only the first five categories that have featured products
    $categories = ProductCategory::whereHas('products', function ($query) {
        $query->where('is_featured', 1)  
         ->where('status', 'published'); // Ensure there are featured products
    })
    ->with(['products' => function ($query) {
        $query->where('is_featured', 1) 
          ->where('status', 'published'); // Only get featured products
    }])
    ->take(5) // Limit to 5 categories
    ->get();

    // Prepare a subquery for best price and delivery date
    $subQuery = Product::select('sku')
        ->selectRaw('MIN(price) as best_price')
        ->selectRaw('MIN(delivery_days) as best_delivery_date')
        ->groupBy('sku');

    // Map the categories to include featured products with additional info
    $categories = $categories->map(function ($category) use ($subQuery) {
        return [
            'category_name' => $category->name,
            'featured_products' => $category->products->take(10)->map(function ($product) use ($subQuery) {
                // Join with the subquery to get best price and delivery date
                $productDetails = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                    $join->on('ec_products.sku', '=', 'best_products.sku')
                         ->whereColumn('ec_products.price', 'best_products.best_price');
                })
                ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
                ->with('reviews', 'currency')
                ->where('ec_products.id', $product->id) // Only get the current product
                ->first(); // Fetch the product details

                // Count total reviews and calculate average rating
                $totalReviews = $productDetails->reviews->count();
                $avgRating = $totalReviews > 0 ? $productDetails->reviews->avg('star') : null;

                // Calculate left stock
                $quantity = $productDetails->quantity ?? 0;
                $unitsSold = $productDetails->units_sold ?? 0;
                $leftStock = $quantity - $unitsSold;

                // Add currency symbol
                $currencyTitle = $productDetails->currency ? $productDetails->currency->title : $productDetails->price; // Fallback if no currency found

                // Correctly resolve image paths
                $imageUrls = collect($productDetails->images)->map(function ($image) {
                    // Check if the image already has a full URL
                    if (filter_var($image, FILTER_VALIDATE_URL)) {
                        return $image; // Use the full URL as it is
                    }

                    // Dynamically check if the image is in 'storage/products/' or 'storage/' folder
                    $basePaths = [
                        'storage/products/', // First, check 'products/' subfolder
                        'storage/',          // Then, check the general 'storage/' folder
                    ];

                    foreach ($basePaths as $basePath) {
                        $fullPath = asset($basePath . $image);
                        if (file_exists(public_path($basePath . $image))) {
                            return $fullPath; // Return the first valid path
                        }
                    }

                    // Handle fallback for missing paths
                    return null; // Or a default placeholder image
                })->filter(); // Remove null values

                // Return product data with additional info
                return array_merge($productDetails->toArray(), [
                    'total_reviews' => $totalReviews,
                    'avg_rating' => $avgRating,
                    'leftStock' => $leftStock,
                    'currency' => $currencyTitle,
                    'images' => $imageUrls, // Add the full image URLs here
                ]);
            }),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $categories,
    ]);
}

 

}
