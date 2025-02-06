<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Ecommerce\Models\ProductCategory; // Ensure you import the Category model
use Botble\Ecommerce\Models\Product;
use Illuminate\Support\Facades\Auth;
use Botble\Ecommerce\Models\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryApiController extends Controller
{
   
// public function getAllFeaturedProductsByCategory(Request $request)
// {
//     $userId = Auth::id();
//     $isUserLoggedIn = $userId !== null;

//     $wishlistProductIds = $isUserLoggedIn
//         ? DB::table('ec_wish_lists')
//             ->where('customer_id', $userId)
//             ->pluck('product_id')
//             ->map(fn($id) => (int) $id)
//             ->toArray()
//         : session()->get('guest_wishlist', []);

//     $categories = ProductCategory::whereHas('products', function($query) {
//         $query->where('is_featured', 1)->where('status', 'published');
//     })
//     ->with(['products' => function($query) {
//         $query->where('is_featured', 1)->where('status', 'published');
//     }])
//     ->take(5)
//     ->get();

//     $subQuery = Product::select('sku')
//         ->selectRaw('MIN(price) as best_price')
//         ->selectRaw('MIN(delivery_days) as best_delivery_date')
//         ->groupBy('sku');

//     $categories = $categories->map(function ($category) use ($subQuery, $wishlistProductIds) {
//         return [
//             'category_name' => $category->name,
//             'featured_products' => $category->products->take(10)->map(function ($product) use ($subQuery, $wishlistProductIds) {
//                 $productDetails = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
//                     $join->on('ec_products.sku', '=', 'best_products.sku')
//                          ->whereColumn('ec_products.price', 'best_products.best_price');
//                 })
//                 ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
//                 ->with('reviews', 'currency')
//                 ->where('ec_products.id', $product->id)
//                 ->first();

//                 $totalReviews = $productDetails->reviews->count();
//                 $avgRating = $totalReviews > 0 ? $productDetails->reviews->avg('star') : null;
//                 $leftStock = ($productDetails->quantity ?? 0) - ($productDetails->units_sold ?? 0);
//                 $currencyTitle = $productDetails->currency->title ?? $productDetails->price;
//                 $isInWishlist = in_array($productDetails->id, $wishlistProductIds);

//                 $imageUrls = collect($productDetails->images)->map(function ($image) {
//                     if (Str::startsWith($image, ['http://', 'https://'])) {
//                         return $image; // Leave external URLs unchanged
//                     } elseif (Str::startsWith($image, 'storage/products/')) {
//                         return asset($image); // Image inside storage/products folder
//                     } elseif (Str::startsWith($image, 'storage/')) {
//                         return asset($image); // Image inside storage folder
//                     } else {
//                         return asset('storage/' . $image); // Fallback for other images
//                     }
//                 });

//                 return array_merge($productDetails->toArray(), [
//                     'total_reviews' => $totalReviews,
//                     'avg_rating' => $avgRating,
//                     'leftStock' => $leftStock,
//                     'currency' => $currencyTitle,
//                     'in_wishlist' => $isInWishlist,
//                     'images' => $imageUrls,
//                 ]);
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
    $userId = Auth::id();
    $isUserLoggedIn = $userId !== null;

    // Fetch wishlist product IDs for logged-in users or guests
    $wishlistProductIds = $isUserLoggedIn
        ? DB::table('ec_wish_lists')
            ->where('customer_id', $userId)
            ->pluck('product_id')
            ->map(fn($id) => (int) $id)
            ->toArray()
        : session()->get('guest_wishlist', []);

    // Get only third-level child categories that have featured products
    $categories = ProductCategory::whereHas('products', function ($query) {
        $query->where('is_featured', 1)->where('status', 'published');
    })
    ->whereHas('parent.parent') // Ensures only third-level child categories
    ->with(['products' => function ($query) {
        $query->where('is_featured', 1)
              ->where('status', 'published')
              ->select('id', 'name', 'sku', 'price', 'currency_id', 'quantity', 'units_sold'); // Select only necessary fields
    }])
    ->take(5)
    ->get();

    // Subquery for best price and delivery days
    $subQuery = Product::select('sku')
        ->selectRaw('MIN(price) as best_price')
        ->selectRaw('MIN(delivery_days) as best_delivery_date')
        ->groupBy('sku');

    // Process categories and products
    $categories = $categories->map(function ($category) use ($subQuery, $wishlistProductIds) {
        $featuredProducts = $category->products->take(10);

        // Fetch all product details in one query
        $productDetails = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                $join->on('ec_products.sku', '=', 'best_products.sku')
                     ->whereColumn('ec_products.price', 'best_products.best_price');
            })
            ->whereIn('ec_products.id', $featuredProducts->pluck('id'))
            ->with(['reviews', 'currency']) // Eager load relationships
            ->get()
            ->keyBy('id'); // Use keyBy to quickly fetch by ID later

        return [
            'category_name' => $category->name,
            'featured_products' => $featuredProducts->map(function ($product) use ($productDetails, $wishlistProductIds) {
                $details = $productDetails[$product->id] ?? null;
                if (!$details) return null; // Skip if no details found

                $totalReviews = $details->reviews->count();
                $avgRating = $totalReviews > 0 ? $details->reviews->avg('star') : null;
                $leftStock = ($details->quantity ?? 0) - ($details->units_sold ?? 0);
                $currencyTitle = $details->currency->title ?? $details->price;
                $isInWishlist = in_array($details->id, $wishlistProductIds);

                // Process images efficiently
                $imageUrls = collect($details->images)->map(fn($image) => Str::startsWith($image, ['http://', 'https://']) ? $image : asset('storage/' . ltrim($image, '/')));

                return [
                    'id' => $details->id,
                    'name' => $details->name,
                    'sku' => $details->sku,
                    'price' => $details->best_price ?? $details->price,
                    "sale_price" => $details->sale_price,
                    'best_delivery_date' => $details->best_delivery_date,
                    'total_reviews' => $totalReviews,
                    'avg_rating' => $avgRating,
                    'left_stock' => $leftStock,
                    'currency' => $currencyTitle,
                    'in_wishlist' => $isInWishlist,
                    'images' => $imageUrls,
                    "original_price"=> $details->price,
                    "front_sale_price"=> $details->price,
                    "best_price"=> $details->price,
                ];
            })->filter()->values(), // Remove null values and reset array keys
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $categories,
    ]);
}


// public function getAllGuestFeaturedProductsByCategory(Request $request)
// {
//     // Fetch only the first five categories that have featured products
//     $categories = ProductCategory::whereHas('products', function ($query) {
//         $query->where('is_featured', 1)  
//          ->where('status', 'published'); // Ensure there are featured products
//     })
//     ->with(['products' => function ($query) {
//         $query->where('is_featured', 1) 
//           ->where('status', 'published'); // Only get featured products
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

//                 // Correctly resolve image paths
//                 $imageUrls = collect($productDetails->images)->map(function ($image) {
//                     // Check if the image already has a full URL
//                     if (filter_var($image, FILTER_VALIDATE_URL)) {
//                         return $image; // Use the full URL as it is
//                     }

//                     // Dynamically check if the image is in 'storage/products/' or 'storage/' folder
//                     $basePaths = [
//                         'storage/products/', // First, check 'products/' subfolder
//                         'storage/',          // Then, check the general 'storage/' folder
//                     ];

//                     foreach ($basePaths as $basePath) {
//                         $fullPath = asset($basePath . $image);
//                         if (file_exists(public_path($basePath . $image))) {
//                             return $fullPath; // Return the first valid path
//                         }
//                     }

//                     // Handle fallback for missing paths
//                     return null; // Or a default placeholder image
//                 })->filter(); // Remove null values

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
 
   
    // Get only third-level child categories that have featured products
    $categories = ProductCategory::whereHas('products', function ($query) {
        $query->where('is_featured', 1)->where('status', 'published');
    })
    ->whereHas('parent.parent') // Ensures only third-level child categories
    ->with(['products' => function ($query) {
        $query->where('is_featured', 1)
              ->where('status', 'published')
              ->select('id', 'name', 'sku', 'price', 'currency_id', 'quantity', 'units_sold'); // Select only necessary fields
    }])
    ->take(5)
    ->get();

    // Subquery for best price and delivery days
    $subQuery = Product::select('sku')
        ->selectRaw('MIN(price) as best_price')
        ->selectRaw('MIN(delivery_days) as best_delivery_date')
        ->groupBy('sku');

    // Process categories and products
    $categories = $categories->map(function ($category) use ($subQuery) {
        $featuredProducts = $category->products->take(10);

        // Fetch all product details in one query
        $productDetails = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                $join->on('ec_products.sku', '=', 'best_products.sku')
                     ->whereColumn('ec_products.price', 'best_products.best_price');
            })
            ->whereIn('ec_products.id', $featuredProducts->pluck('id'))
            ->with(['reviews', 'currency']) // Eager load relationships
            ->get()
            ->keyBy('id'); // Use keyBy to quickly fetch by ID later

        return [
            'category_name' => $category->name,
            'featured_products' => $featuredProducts->map(function ($product) use ($productDetails) {
                $details = $productDetails[$product->id] ?? null;
                if (!$details) return null; // Skip if no details found

                $totalReviews = $details->reviews->count();
                $avgRating = $totalReviews > 0 ? $details->reviews->avg('star') : null;
                $leftStock = ($details->quantity ?? 0) - ($details->units_sold ?? 0);
                $currencyTitle = $details->currency->title ?? $details->price;

                // Process images efficiently
                $imageUrls = collect($details->images)->map(fn($image) => Str::startsWith($image, ['http://', 'https://']) ? $image : asset('storage/' . ltrim($image, '/')));

                return [
                    'id' => $details->id,
                    'name' => $details->name,
                    'sku' => $details->sku,
                    'price' => $details->best_price ?? $details->price,
                    "sale_price" => $details->sale_price,
                    'best_delivery_date' => $details->best_delivery_date,
                    'total_reviews' => $totalReviews,
                    'avg_rating' => $avgRating,
                    'left_stock' => $leftStock,
                    'currency' => $currencyTitle,
                    'images' => $imageUrls,
                    "original_price"=> $details->price,
                    "front_sale_price"=> $details->price,
                    "best_price"=> $details->price,
                ];
            })->filter()->values(), // Remove null values and reset array keys
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $categories,
    ]);
}


}
