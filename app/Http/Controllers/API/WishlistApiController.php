<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistApiController extends Controller
{
    // Method to add a product to the wishlist
    public function addToWishlist(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:ec_products,id',
        ]);

        if (Auth::check()) {
            // Authenticated user - save to database
            $customerId = Auth::id();
            $wishlist = Wishlist::updateOrCreate(
                [
                    'customer_id' => $customerId,
                    'product_id' => $validated['product_id'],
                ]
            );

            return response()->json([
                'message' => 'Product added to wishlist',
                'wishlist' => [
                    'customer_id' => $wishlist->customer_id,
                    'product_id' => $wishlist->product_id,
                    'in_wishlist' => 1,
                    'created_at' => $wishlist->created_at,
                    'updated_at' => $wishlist->updated_at,
                ]
            ], 201);
        } else {
            // Guest user - save to session
            $wishlist = session()->get('guest_wishlist', []);
            
            // Check if product is already in wishlist
            if (!in_array($validated['product_id'], $wishlist)) {
                $wishlist[] = $validated['product_id'];
                session()->put('guest_wishlist', $wishlist);
            }

            return response()->json([
                'message' => 'Product added to wishlist',
                'wishlist' => [
                    'product_id' => $validated['product_id'],
                    'in_wishlist' => 1,
                ]
            ], 201);
        }
    }

    // Method to get all products in the wishlist
    // public function getWishlist(Request $request)
    // {
    //     if (Auth::check()) {
    //         // Authenticated user - get from database
    //         $userId = Auth::id();
    //         $wishlistItems = Wishlist::with('product')->where('customer_id', $userId)->get();

    //         $wishlistItems->transform(function ($item) {
    //             $item->in_wishlist = 1; // Mark as in wishlist
    //             return $item;
    //         });

    //         return response()->json(['wishlist' => $wishlistItems]);
    //     } else {
    //         // Guest user - get from session
    //         $wishlist = session()->get('guest_wishlist', []);
            
    //         return response()->json([
    //             'wishlist' => array_map(function($productId) {
    //                 return [
    //                     'product_id' => $productId,
    //                     'in_wishlist' => 1
    //                 ];
    //             }, $wishlist)
    //         ]);
    //     }
    // }


    // Method to get all products in the wishlist
// public function getWishlist(Request $request)
// {
//     if (Auth::check()) {
//         // Authenticated user - get from database and order by descending creation date
//         $userId = Auth::id();
//         $wishlistItems = Wishlist::with('product')
//             ->where('customer_id', $userId)
//             ->orderBy('created_at', 'desc') // Order by descending
//             ->get();

//         $wishlistItems->transform(function ($item) {
//             $item->in_wishlist = 1; // Mark as in wishlist
//             return $item;
//         });

//         return response()->json(['wishlist' => $wishlistItems]);
//     } else {
//         // Guest user - get from session and reverse the order
//         $wishlist = session()->get('guest_wishlist', []);
//         $wishlist = array_reverse($wishlist); // Reverse the order for descending

//         return response()->json([
//             'wishlist' => array_map(function($productId) {
//                 return [
//                     'product_id' => $productId,
//                     'in_wishlist' => 1
//                 ];
//             }, $wishlist)
//         ]);
//     }
// }

// Method to get all products in the wishlist
public function getWishlist(Request $request)
{
    if (Auth::check()) {
        // Authenticated user - get wishlist from the database
        $userId = Auth::id();
        $wishlistItems = Wishlist::with('product')->
        where('customer_id', $userId) ->orderBy('created_at', 'desc')->get();

        $wishlistItems->transform(function ($item) {
            $product = $item->product;

            if ($product) {
                // Ensure `images` is properly formatted
                $product->images = collect($product->images)->map(function ($image) {
                    if (strpos($image, '/storage/products/') !== false) {
                        return asset($image); // Add base URL for images already in storage/products
                    } else {
                        return asset('storage/' . $image); // Add base URL for images in storage
                    }
                });

                $product->in_wishlist = 1; // Mark as in wishlist
            }

            return $item;
        });

        return response()->json(['wishlist' => $wishlistItems]);
    } else {
        // Guest user - get wishlist from the session
        $wishlist = session()->get('guest_wishlist', []);

        $formattedWishlist = array_map(function ($productId) {
            $product = Product::find($productId);

            if ($product) {
                // Ensure `images` is properly formatted
                $product->images = collect($product->images)->map(function ($image) {
                    if (strpos($image, '/storage/products/') !== false) {
                        return asset($image); // Add base URL for images already in storage/products
                    } else {
                        return asset('storage/' . $image); // Add base URL for images in storage
                    }
                });

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'images' => $product->images,
                    'in_wishlist' => 1,
                ];
            }

            return null;
        }, $wishlist);

        return response()->json(['wishlist' => array_filter($formattedWishlist)]);
    }
}


    // Method to remove a product from the wishlist
    public function removeFromWishlist(Request $request)
    {
        $productId = $request->query('product_id');

        if (Auth::check()) {
            // Authenticated user - remove from database
            $userId = Auth::id();
            $deleted = Wishlist::where('customer_id', $userId)
                               ->where('product_id', $productId)
                               ->delete();

            if ($deleted) {
                return response()->json([
                    'message' => 'Product removed from wishlist',
                    'in_wishlist' => 0
                ]);
            }

            return response()->json(['message' => 'Product not found in wishlist'], 404);
        } else {
            // Guest user - remove from session
            $wishlist = session()->get('guest_wishlist', []);
            $wishlist = array_filter($wishlist, function($id) use ($productId) {
                return $id != $productId;
            });

            session()->put('guest_wishlist', $wishlist);

            return response()->json([
                'message' => 'Product removed from wishlist',
                'in_wishlist' => 0
            ]);
        }
    }
}



// namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
// use Botble\Ecommerce\Models\Wishlist;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;

// class WishlistApiController extends Controller
// {
//     // Method to add a product to the wishlist
//     public function addToWishlist(Request $request)
//     {
//         // Validate the incoming request data
//         $validated = $request->validate([
//             'product_id' => 'required|integer|exists:ec_products,id',
//         ]);

//         // Check if the user is authenticated
//         if (Auth::check()) {
//             $customerId = Auth::id();

//             // Create or update the wishlist item for authenticated users
//             $wishlist = Wishlist::updateOrCreate(
//                 [
//                     'customer_id' => $customerId,
//                     'product_id' => $validated['product_id'],
//                 ]
//             );

//             // Return the wishlist item with its details in the response
//             return response()->json([
//                 'message' => 'Product added to wishlist',
//                 'wishlist' => [
//                     'customer_id' => $wishlist->customer_id,
//                     'product_id' => $wishlist->product_id,
//                     'created_at' => $wishlist->created_at,
//                     'updated_at' => $wishlist->updated_at,
//                 ]
//             ], 201);
//         }

//         return response()->json(['message' => 'Guests cannot add products to the wishlist'], 403);
//     }

//     // Method to get all products in the wishlist
//     public function getWishlist(Request $request)
//     {
//         // Check if the user is authenticated
//         if (Auth::check()) {
//             $userId = Auth::id();
//             $wishlistItems = Wishlist::with('product')->where('customer_id', $userId)->get();

//             return response()->json(['wishlist' => $wishlistItems]);
//         }

//         return response()->json(['message' => 'Guests cannot view the wishlist'], 403);
//     }

// // Method to remove a product from the wishlist using product_id
// public function removeFromWishlist(Request $request)
// {
//     // Get the product_id from the query parameter
//     $productId = $request->query('product_id');

//     // Check if the user is authenticated
//     if (Auth::check()) {
//         $userId = Auth::id();

//         // Find and delete the wishlist item by customer_id and product_id
//         $deleted = Wishlist::where('customer_id', $userId)
//                           ->where('product_id', $productId)
//                           ->delete();

//         if ($deleted) {
//             return response()->json(['message' => 'Product removed from wishlist']);
//         }

//         return response()->json(['message' => 'Product not found in wishlist'], 404);
//     }

//     return response()->json(['message' => 'Guests cannot remove products from the wishlist'], 403);
// }

// }
