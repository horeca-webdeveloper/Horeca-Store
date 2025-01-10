<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Botble\Ecommerce\Models\Cart;
use Botble\Ecommerce\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // Import the Storage facade


class CartApiController extends Controller
{

// public function addToCart(Request $request)
// {
//     $request->validate([
//         'product_id' => 'required|exists:ec_products,id',
//         'quantity' => 'required|integer|min:1',
//     ]);

//     $productId = $request->input('product_id');
//     $quantity = $request->input('quantity');

//     if (Auth::check()) {
//         $userId = Auth::id();

//         // Check if the user has already added this product
//         $cartItem = Cart::where('user_id', $userId)
//                         ->where('product_id', $productId)
//                         ->first();

//         if ($cartItem) {
//             \Log::info('Cart item already exists', ['cartItem' => $cartItem]);

//             // Only update if the current quantity differs
//             if ($cartItem->quantity != $quantity) {
//                 \Log::info('Updating cart item with new quantity', ['old_quantity' => $cartItem->quantity, 'new_quantity' => $quantity]);
//                 $cartItem->quantity = $quantity;
//                 $cartItem->save();
//             } else {
//                 \Log::info('Quantity is the same, no update needed');
//             }
//         } else {
//             \Log::info('No cart item found, creating new');
//             $cartItem = Cart::create([
//                 'user_id' => $userId,
//                 'product_id' => $productId,
//                 'quantity' => $quantity,
//             ]);
//         }

//         $cartItem = Cart::with('product.currency')->find($cartItem->id);

//         return response()->json([
//             'success' => true,
//             'data' => [
//                 'id' => $cartItem->id,
//                 'user_id' => $cartItem->user_id,
//                 'product_id' => $cartItem->product_id,
//                 'quantity' => $cartItem->quantity,
//                 'currency_id' => $cartItem->product->currency->id,
//                 'currency_title' => $cartItem->product->currency->title,
//             ],
//         ]);
//     }

//     return response()->json([
//         'success' => false,
//         'message' => 'Unauthorized user',
//     ], 200);
// }



public function addToCart(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:ec_products,id',
        'quantity' => 'required|integer|min:1',
    ]);

    $productId = $request->input('product_id');
    $quantity = $request->input('quantity');

    if (Auth::check()) {
        $userId = Auth::id();

        // Check if the user has already added this product
        $cartItem = Cart::where('user_id', $userId)
                        ->where('product_id', $productId)
                        ->first();

        if ($cartItem) {
            \Log::info('Cart item already exists', ['cartItem' => $cartItem]);

            // Update the quantity by adding the new quantity
            \Log::info('Updating cart item with added quantity', ['old_quantity' => $cartItem->quantity, 'added_quantity' => $quantity]);
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            \Log::info('No cart item found, creating new');
            $cartItem = Cart::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        $cartItem = Cart::with('product.currency')->find($cartItem->id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cartItem->id,
                'user_id' => $cartItem->user_id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'currency_id' => $cartItem->product->currency->id,
                'currency_title' => $cartItem->product->currency->title,
            ],
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Unauthorized user',
    ], 200);
}


 
         
            
    // public function viewCart(Request $request)
    //         {
    //             // Determine if the user is logged in and get the user ID
    //             $userId = Auth::id();
    //             $isUserLoggedIn = $userId !== null;
            
    //             // Log the login status for debugging purposes
    //             Log::info('User logged in:', ['user_id' => $userId]);
            
    //             // Get wishlist product IDs
    //             $wishlistProductIds = [];
    //             if ($isUserLoggedIn) {
    //                 // Fetch wishlist items for the logged-in user
    //                 $wishlistProductIds = DB::table('ec_wish_lists')
    //                     ->where('customer_id', $userId)
    //                     ->pluck('product_id')
    //                     ->map(function($id) {
    //                         return (int) $id;
    //                     })
    //                     ->toArray();
    //             } else {
    //                 // Handle guest wishlist (stored in session)
    //                 $wishlistProductIds = session()->get('guest_wishlist', []);
    //             }
            
    //             // Fetch cart items with product and currency details
    //             $cartItems = Auth::check()
    //                 ? Cart::where('user_id', $userId)->with('product.currency')->get()
    //                 : Cart::where('session_id', $request->session()->getId())->with('product.currency')->get();
            
    //             // Add 'is_wishlist' flag to each product in cart items
    //             $cartItems->each(function($item) use ($wishlistProductIds) {
    //                 $item->product->in_wishlist = in_array($item->product->id, $wishlistProductIds);
    //             });
            
    //             // Extract currency titles into a separate array
    //             $currencyTitles = $cartItems->pluck('product.currency.title')->unique()->filter()->values();
            
    //             return response()->json([
    //                 'success' => true,
    //                 'currency_title' => $currencyTitles,
    //                 'data' => $cartItems,
    //             ]);
    //         }

//     public function viewCart(Request $request)
// {
//     $userId = Auth::id();
//     $isUserLoggedIn = $userId !== null;

//     Log::info('User logged in:', ['user_id' => $userId]);

//     // Get wishlist product IDs
//     $wishlistProductIds = $isUserLoggedIn
//         ? DB::table('ec_wish_lists')
//             ->where('customer_id', $userId)
//             ->pluck('product_id')
//             ->map(function ($id) {
//                 return (int) $id;
//             })
//             ->toArray()
//         : session()->get('guest_wishlist', []);

//     // Fetch cart items with product and currency details
//     $cartItems = Auth::check()
//         ? Cart::where('user_id', $userId)->with('product.currency')->get()
//         : Cart::where('session_id', $request->session()->getId())->with('product.currency')->get();

//     // Add 'is_wishlist' flag and generate full URLs for product images
//     $cartItems->each(function ($item) use ($wishlistProductIds) {
//         $item->product->in_wishlist = in_array($item->product->id, $wishlistProductIds);
        
//         // Generate full URLs for images
//         $baseImageUrl = url('storage/products/');
//         $item->product->images = collect($item->product->images)->map(function ($image) use ($baseImageUrl) {
//             return $baseImageUrl . '/' . $image;
//         });
        
//         // Add full URL for the main product image
//         $item->product->image = $item->product->image ? $baseImageUrl . '/' . $item->product->image : null;
//     });

//     $currencyTitles = $cartItems->pluck('product.currency.title')->unique()->filter()->values();

//     return response()->json([
//         'success' => true,
//         'currency_title' => $currencyTitles,
//         'data' => $cartItems,
//     ]);
// }

public function viewCart(Request $request)
{
    $userId = Auth::id();
    $isUserLoggedIn = $userId !== null;

    Log::info('User logged in:', ['user_id' => $userId]);

    // Get wishlist product IDs
    $wishlistProductIds = $isUserLoggedIn
        ? DB::table('ec_wish_lists')
            ->where('customer_id', $userId)
            ->pluck('product_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray()
        : session()->get('guest_wishlist', []); 

    // Fetch cart items with product and currency details
    $cartItems = Auth::check()
        ? Cart::where('user_id', $userId)->with('product.currency')->get()
        : Cart::where('session_id', $request->session()->getId())->with('product.currency')->get();

    // Add 'is_wishlist' flag and generate full URLs for product images
    $cartItems->each(function ($item) use ($wishlistProductIds) {
        $item->product->in_wishlist = in_array($item->product->id, $wishlistProductIds);

        // Generate full URLs for images
        $baseStorageUrl = url('storage/');
        $baseProductsUrl = url('storage/products/');
        
        // Generate full URLs for product images
        $item->product->images = collect($item->product->images ?? [])->map(function ($image) use ($baseStorageUrl, $baseProductsUrl) {
            // Check if the image exists in 'products' directory, else use the general 'storage' directory
            $url = Storage::exists('products/' . $image) ? $baseProductsUrl : $baseStorageUrl;
            return $url . '/' . $image;  // Ensure there's a '/' between the base URL and image file
        });

        // Add full URL for the main product image
        if ($item->product->image) {
            $imagePath = 'products/' . $item->product->image;
            $url = Storage::exists($imagePath) ? $baseProductsUrl : $baseStorageUrl;
            $item->product->image = $url . '/' . $item->product->image;  // Ensure '/' is added here
        } else {
            $item->product->image = null;
        }
    });

    $currencyTitles = $cartItems->pluck('product.currency.title')->unique()->filter()->values();

    return response()->json([
        'success' => true,
        'currency_title' => $currencyTitles,
        'data' => $cartItems,
    ]);
}



       
    public function clearCart(Request $request)
    {
        if (Auth::check()) {
            Cart::where('user_id', Auth::id())->delete();
        } else {
            Cart::where('session_id', $request->session()->getId())->delete();
        }

        return response()->json([
            'success' => true,
              'messege' => 'Clear Cart Successfully',
        ]);
    }
    public function clearProductFromCart(Request $request, $productId)
    {
    // Determine if the user is logged in and get the user ID
    $userId = Auth::id();

    if (Auth::check()) {
        // Remove the product from the cart for logged-in user
        Cart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();
    } else {
        // Remove the product from the cart for guest user (using session ID)
        Cart::where('session_id', $request->session()->getId())
            ->where('product_id', $productId)
            ->delete();
    }

    return response()->json(['success' => true]);
}

   
    public function updateCartQuantity(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:ec_products,id',
            'quantity' => 'required|integer|min:1',
        ]);
    
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
    
        if (Auth::check()) {
            $userId = Auth::id();
            $cartItem = Cart::where('user_id', $userId)->where('product_id', $productId)->with('product.currency')->first();
        } else {
            $sessionId = $request->session()->getId();
            $cartItem = Cart::where('session_id', $sessionId)->where('product_id', $productId)->with('product.currency')->first();
        }
    
        if ($cartItem) {
            $cartItem->quantity = $quantity;
            $cartItem->save();
    
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cartItem->id,
                    'user_id' => $cartItem->user_id,
                    'session_id' => $cartItem->session_id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'currency_title' => $cartItem->product->currency->title ?? null, // Currency title inside data
                    'created_at' => $cartItem->created_at,
                    'updated_at' => $cartItem->updated_at,
                ],
            ]);
        }
    
        return response()->json(['success' => false, 'message' => 'Item not found in cart.'], 404);
    }


    // public function addToCartGuest(Request $request)
    // {
    //     // Validate the request input
    //     $request->validate([
    //         'product_id' => 'required|exists:ec_products,id',
    //         'quantity' => 'required|integer|min:1',
    //     ]);
    
    //     $productId = $request->input('product_id');
    //     $quantity = $request->input('quantity');
    //     $userId = Auth::check() ? Auth::id() : null; // Get authenticated user ID
    //     $sessionId = $userId ? null : $request->session()->getId(); // Get session ID for guests
    
    //     // Query to find existing cart item
    //     $cartItem = Cart::where(function($query) use ($userId, $sessionId) {
    //         if ($userId) {
    //             $query->where('user_id', $userId);
    //         } else {
    //             $query->where('session_id', $sessionId);
    //         }
    //     })
    //     ->where('product_id', $productId)
    //     ->first();
    
    //     if ($cartItem) {
    //         // Update quantity if item already in cart
    //         $cartItem->quantity += $quantity;
    //         $cartItem->save();
    //     } else {
    //         // Create new cart item
    //         Cart::create([
    //             'user_id' => $userId,
    //             'session_id' => $sessionId,
    //             'product_id' => $productId,
    //             'quantity' => $quantity,
    //         ]);
    //     }
    
    //     // Fetch the current cart items
    //     $cartItems = Cart::where(function($query) use ($userId, $sessionId) {
    //         if ($userId) {
    //             $query->where('user_id', $userId);
    //         } else {
    //             $query->where('session_id', $sessionId);
    //         }
    //     })->with('product')
    //       ->get();
    
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Product added to cart',
    //         'cart' => $cartItems
    //     ]);
    // }



    
    public function decreaseQuantity(Request $request)
    {
        // Validate request inputs
        $request->validate([
            'product_id' => 'required|exists:ec_products,id',
            'quantity' => 'required|integer|min:1',
        ]);
    
        $productId = $request->input('product_id');
        $quantityToDecrease = $request->input('quantity');
    
        // Determine if the user is logged in and retrieve the cart item
        $cartItem = null;
        if (Auth::check()) {
            $userId = Auth::id();
            $cartItem = Cart::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();
        } else {
            $sessionId = $request->session()->getId();
            $cartItem = Cart::where('session_id', $sessionId)
                ->where('product_id', $productId)
                ->first();
        }
    
        // Check if the cart item exists
        if (!$cartItem) {
            Log::info('Cart item not found for product', [
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'session_id' => $request->session()->getId()
            ]);
            return response()->json(['success' => false, 'message' => 'Item not found in cart.'], 404);
        }
    
        // Decrease the quantity and check if it should be removed
        $cartItem->quantity -= $quantityToDecrease;
    
        if ($cartItem->quantity <= 0) {
            $cartItem->delete();
            return response()->json(['success' => true, 'message' => 'Item removed from cart.']);
        } else {
            $cartItem->save();
    
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cartItem->id,
                    'user_id' => $cartItem->user_id,
                    'session_id' => $cartItem->session_id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'created_at' => $cartItem->created_at,
                    'updated_at' => $cartItem->updated_at,
                ],
            ]);
        }
    }

    


    public function viewCartGuest(Request $request)
    {
        $cartItems = Cart::where('session_id', $request->session()->getId())->with('product')->get();

        return response()->json([
            'success' => true,
            'data' => $cartItems,
        ]);
    }
    
    public function clearCartGuest(Request $request)
    {
        // Delete all items from the cart for a guest user (based on session ID)
        Cart::where('session_id', $request->session()->getId())->delete();
    
        return response()->json(['success' => true]);
    }

//     public function clearCart(Request $request)
// {
//     // Check if the user is logged in
//     if (Auth::check()) {
//         // If logged in, delete all items from the user's cart
//         Cart::where('user_id', Auth::id())->delete();
//     } else {
//         // If not logged in (guest user), delete all items from the session's cart
//         Cart::where('session_id', $request->session()->getId())->delete();
//     }

//     // Return a success response
//     return response()->json(['success' => true]);
// }

}





// class CartApiController extends Controller
// {
//     public function addToCart(Request $request)
//     {
//         $request->validate([
//             'product_id' => 'required|exists:ec_products,id',
//             'quantity' => 'required|integer|min:1',
//         ]);

//         $productId = $request->input('product_id');
//         $quantity = $request->input('quantity');

//         if (Auth::check()) {
//             // Logged-in user
//             $userId = Auth::id();
//             $cartItem = Cart::updateOrCreate(
//                 ['user_id' => $userId, 'product_id' => $productId],
//                 ['quantity' => \DB::raw("quantity + $quantity")]
//             );
//         } else {
//             // Guest user
//             $sessionId = $request->session()->getId();
//             $cartItem = Cart::updateOrCreate(
//                 ['session_id' => $sessionId, 'product_id' => $productId],
//                 ['quantity' => \DB::raw("quantity + $quantity")]
//             );
//         }

//         $cartItem = Cart::find($cartItem->id); // Get the cart item again with updated values

//         return response()->json([
//             'success' => true,
//             'data' => [
//                 'id' => $cartItem->id,
//                 'user_id' => $cartItem->user_id,
//                 'session_id' => $cartItem->session_id,
//                 'product_id' => $cartItem->product_id,
//                 'quantity' => $cartItem->quantity,
//                 'created_at' => $cartItem->created_at,
//                 'updated_at' => $cartItem->updated_at,
//             ],
//         ]);
//     }

//     public function viewCart(Request $request)
//     {
//         $cartItems = Auth::check() 
//             ? Cart::where('user_id', Auth::id())->with('product')->get() 
//             : Cart::where('session_id', $request->session()->getId())->with('product')->get();

//         return response()->json([
//             'success' => true,
//             'data' => $cartItems,
//         ]);
//     }

//     public function clearCart(Request $request)
//     {
//         if (Auth::check()) {
//             Cart::where('user_id', Auth::id())->delete();
//         } else {
//             Cart::where('session_id', $request->session()->getId())->delete();
//         }

//         return response()->json(['success' => true]);
//     }

//     // Update cart quantity method
//     public function updateCartQuantity(Request $request)
//     {
//         $request->validate([
//             'product_id' => 'required|exists:ec_products,id',
//             'quantity' => 'required|integer|min:1',
//         ]);

//         $productId = $request->input('product_id');
//         $quantity = $request->input('quantity');

//         if (Auth::check()) {
//             $userId = Auth::id();
//             $cartItem = Cart::where('user_id', $userId)->where('product_id', $productId)->first();
//         } else {
//             $sessionId = $request->session()->getId();
//             $cartItem = Cart::where('session_id', $sessionId)->where('product_id', $productId)->first();
//         }

//         if ($cartItem) {
//             $cartItem->quantity = $quantity;
//             $cartItem->save();

//             return response()->json([
//                 'success' => true,
//                 'data' => [
//                     'id' => $cartItem->id,
//                     'user_id' => $cartItem->user_id,
//                     'session_id' => $cartItem->session_id,
//                     'product_id' => $cartItem->product_id,
//                     'quantity' => $cartItem->quantity,
//                     'created_at' => $cartItem->created_at,
//                     'updated_at' => $cartItem->updated_at,
//                 ],
//             ]);
//         }

//         return response()->json(['success' => false, 'message' => 'Item not found in cart.'], 404);
//     }

//     // Additional methods for guest users
//     public function addToCartGuest(Request $request)
//     {
//         $request->validate([
//             'product_id' => 'required|exists:ec_products,id',
//             'quantity' => 'required|integer|min:1',
//         ]);
    
//         $productId = $request->input('product_id');
//         $quantity = $request->input('quantity');
//         $sessionId = $request->session()->getId();
    
//         $cartItem = Cart::where('session_id', $sessionId)
//             ->where('product_id', $productId)
//             ->first();
    
//         if ($cartItem) {
//             $cartItem->quantity += $quantity;
//             $cartItem->save();
//         } else {
//             $cartItem = Cart::create([
//                 'session_id' => $sessionId,
//                 'product_id' => $productId,
//                 'quantity' => $quantity,
//             ]);
//         }

//         return response()->json([
//             'success' => true,
//             'data' => [
//                 'id' => $cartItem->id,
//                 'user_id' => $cartItem->user_id,
//                 'session_id' => $cartItem->session_id,
//                 'product_id' => $cartItem->product_id,
//                 'quantity' => $cartItem->quantity,
//                 'created_at' => $cartItem->created_at,
//                 'updated_at' => $cartItem->updated_at,
//             ],
//         ]);
//     }
    
//     public function decreaseQuantity(Request $request)
//     {
//         $request->validate([
//             'product_id' => 'required|exists:ec_products,id',
//             'quantity' => 'required|integer|min:1',
//         ]);

//         $productId = $request->input('product_id');

//         if (Auth::check()) {
//             $userId = Auth::id();
//             $cartItem = Cart::where('user_id', $userId)
//                 ->where('product_id', $productId)
//                 ->first();
//         } else {
//             $sessionId = $request->session()->getId();
//             $cartItem = Cart::where('session_id', $sessionId)
//                 ->where('product_id', $productId)
//                 ->first();
//         }

//         if ($cartItem) {
//             $cartItem->quantity -= $request->input('quantity');

//             if ($cartItem->quantity <= 0) {
//                 $cartItem->delete();
//                 return response()->json(['success' => true, 'message' => 'Item removed from cart.']);
//             }

//             $cartItem->save();

//             return response()->json([
//                 'success' => true,
//                 'data' => [
//                     'id' => $cartItem->id,
//                     'user_id' => $cartItem->user_id,
//                     'session_id' => $cartItem->session_id,
//                     'product_id' => $cartItem->product_id,
//                     'quantity' => $cartItem->quantity,
//                     'created_at' => $cartItem->created_at,
//                     'updated_at' => $cartItem->updated_at,
//                 ],
//             ]);
//         }

//         return response()->json(['success' => false, 'message' => 'Item not found in cart.'], 404);
//     }

//     public function viewCartGuest(Request $request)
//     {
//         $cartItems = Cart::where('session_id', $request->session()->getId())->with('product')->get();

//         return response()->json([
//             'success' => true,
//             'data' => $cartItems,
//         ]);
//     }

//     public function clearCartGuest(Request $request)
//     {
//         Cart::where('session_id', $request->session()->getId())->delete();

//         return response()->json(['success' => true]);
//     }
// }
