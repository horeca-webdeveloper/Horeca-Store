<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistCountApiController extends Controller
{
    // Method to get the total count of wishlist items
    public function getWishlistCount(Request $request)
    {
        if (Auth::check()) {
            // Authenticated user - count items from the database
            $userId = Auth::id();
            $wishlistCount = Wishlist::where('customer_id', $userId)->count();
        } else {
            // Guest user - count items from the session
            $wishlist = session()->get('guest_wishlist', []);
            $wishlistCount = count($wishlist);
        }

        return response()->json([
            'success' => true,
            'count' => $wishlistCount,
        ]);
    }
}
