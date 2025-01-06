<?php
// namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Botble\Ecommerce\Models\Cart;

// class CartTotalApiController extends Controller
// {
//     // Method to get total number of products in cart for logged-in users
//     public function totalProductsInCart(Request $request)
//     {
//         $userId = Auth::id();

//         $totalQuantity = Cart::where('user_id', $userId)
//             ->sum('quantity');

//         return response()->json(['total' => $totalQuantity]);
//     }

//     // Method to get total number of products in cart for guest users
//     public function totalProductsInCartGuest(Request $request)
//     {
//         $sessionId = $request->session()->getId();

//         $totalQuantity = Cart::where('session_id', $sessionId)
//             ->sum('quantity');

//         return response()->json(['total' => $totalQuantity]);
//     }
// }
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Botble\Ecommerce\Models\Cart;

class CartTotalApiController extends Controller
{
    // Method to get total number of products in cart for logged-in users
    public function totalProductsInCart(Request $request)
    {
        $userId = Auth::id();

        $totalQuantity = Cart::where('user_id', $userId)->sum('quantity');

        return response()->json(['total' => $totalQuantity]);
    }


    // Method to get total number of products in cart for guest users
   public function totalProductsInCartGuest(Request $request)
    {
        // Get the session ID from the current request
        $sessionId = $request->session()->getId();
    
        // Get the total quantity of items for the guest session
        $totalQuantity = Cart::where('session_id', $sessionId)->sum('quantity');
    
        return response()->json(['total' => $totalQuantity]);
    }
}
