<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Discount;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerCouponApiController extends Controller
{
    /**
     * Fetch all coupons and discounts available for the logged-in customer.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function getCustomerCoupons(Request $request)
    // {
    //     // Get the authenticated user ID
    //     $userId = Auth::id();

    //     if (!$userId) {
    //         return response()->json(['message' => 'User not authenticated.'], 401);
    //     }

    //     // Fetch discounts linked to the customer from the ec_discount_customers table
    //     $discounts = Discount::whereHas('customers', function ($query) use ($userId) {
    //         $query->where('customer_id', $userId);
    //     })
    //     ->where(function ($query) {
    //         // Check if the discount is still valid
    //         $query->where('end_date', '>=', Carbon::now())
    //               ->orWhereNull('end_date');
    //     })
    //     ->get();

    //     if ($discounts->isEmpty()) {
    //         return response()->json(['message' => 'No coupons or discounts available for this customer.'], 404);
    //     }

    //     // Format response with necessary details
    //     $coupons = $discounts->map(function ($discount) {
    //         return [
    //             'id' => $discount->id,
    //             'code' => $discount->code,
    //             'value' => $discount->value,
    //             'type' => $discount->type, // e.g., percentage or fixed amount
    //             'min_order_price' => $discount->min_order_price,
    //             'start_date' => $discount->start_date,
    //             'end_date' => $discount->end_date,
    //         ];
    //     });

    //     return response()->json(['coupons' => $coupons]);
    // }

    public function getCustomerCoupons(Request $request)
{
    $userId = Auth::id();

    if (!$userId) {
        return response()->json(['message' => 'User not authenticated.'], 401);
    }

    // Get all coupons related to the customer
    $allCoupons = Discount::whereHas('customers', function ($query) use ($userId) {
            $query->where('customer_id', $userId);
        })
        ->get()
        ->map(function ($discount) {
            return [
                'id' => $discount->id,
                'code' => $discount->code,
                'value' => $discount->value,
                'type' => $discount->type,
                'min_order_price' => $discount->min_order_price,
                'start_date' => $discount->start_date,
                'end_date' => $discount->end_date,
            ];
        });

    // Get used coupons from ec_customer_used_coupons table
    $usedCoupons = DB::table('ec_customer_used_coupons')
        ->join('ec_discounts', 'ec_customer_used_coupons.discount_id', '=', 'ec_discounts.id')
        ->where('ec_customer_used_coupons.customer_id', $userId)
        ->get(['ec_discounts.id', 'ec_discounts.code', 'ec_discounts.value', 'ec_discounts.type', 'ec_discounts.min_order_price', 'ec_discounts.start_date', 'ec_discounts.end_date']);

    // Get expired coupons (past end_date)
    $expiredCoupons = Discount::whereHas('customers', function ($query) use ($userId) {
            $query->where('customer_id', $userId);
        })
        ->where('end_date', '<', Carbon::now()) // Coupons that have expired
        ->get()
        ->map(function ($discount) {
            return [
                'id' => $discount->id,
                'code' => $discount->code,
                'value' => $discount->value,
                'type' => $discount->type,
                'min_order_price' => $discount->min_order_price,
                'start_date' => $discount->start_date,
                'end_date' => $discount->end_date,
            ];
        });

    // Get available (valid) coupons
    $availableCoupons = Discount::whereHas('customers', function ($query) use ($userId) {
            $query->where('customer_id', $userId);
        })
        ->where(function ($query) {
            $query->where('end_date', '>=', Carbon::now())
                  ->orWhereNull('end_date');
        })
        ->get()
        ->map(function ($discount) {
            return [
                'id' => $discount->id,
                'code' => $discount->code,
                'value' => $discount->value,
                'type' => $discount->type,
                'min_order_price' => $discount->min_order_price,
                'start_date' => $discount->start_date,
                'end_date' => $discount->end_date,
            ];
        });

    return response()->json([
        'all_coupons' => $allCoupons, // This includes all coupons linked to the customer
        'available_coupons' => $availableCoupons,
        'used_coupons' => $usedCoupons,
        'expired_coupons' => $expiredCoupons
    ]);
}


//     public function searchCustomerCoupons(Request $request)
// {
//     $userId = Auth::id();

//     if (!$userId) {
//         return response()->json(['message' => 'User not authenticated.'], 401);
//     }

//     $searchTerm = $request->input('query');

//     $discounts = Discount::whereHas('customers', function ($query) use ($userId) {
//         $query->where('customer_id', $userId);
//     })
//     ->where(function ($query) use ($searchTerm) {
//         $query->where('code', 'LIKE', "%{$searchTerm}%")
//               ->orWhere('type', 'LIKE', "%{$searchTerm}%")
//               ->orWhere('value', 'LIKE', "%{$searchTerm}%");
//     })
//     ->where(function ($query) {
//         $query->where('end_date', '>=', Carbon::now())
//               ->orWhereNull('end_date');
//     })
//     ->get();

//     if ($discounts->isEmpty()) {
//         return response()->json(['message' => 'No matching coupons found.'], 404);
//     }

//     $coupons = $discounts->map(function ($discount) {
//         return [
//             'id' => $discount->id,
//             'code' => $discount->code,
//             'value' => $discount->value,
//             'type' => $discount->type,
//             'min_order_price' => $discount->min_order_price,
//             'start_date' => $discount->start_date,
//             'end_date' => $discount->end_date,
//         ];
//     });

//     return response()->json(['coupons' => $coupons]);
// }


public function searchCustomerCoupons(Request $request)
{
    $userId = Auth::id();

    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated.'
        ], 200);
    }

    $searchTerm = $request->input('query');

    $discounts = Discount::whereHas('customers', function ($query) use ($userId) {
        $query->where('customer_id', $userId);
    })
    ->where(function ($query) use ($searchTerm) {
        $query->where('code', 'LIKE', "%{$searchTerm}%")
              ->orWhere('type', 'LIKE', "%{$searchTerm}%")
              ->orWhere('value', 'LIKE', "%{$searchTerm}%");
    })
    ->where(function ($query) {
        $query->where('end_date', '>=', Carbon::now())
              ->orWhereNull('end_date');
    })
    ->get();

    if ($discounts->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No matching coupons found.'
        ], 200);
    }

    $coupons = $discounts->map(function ($discount) {
        return [
            'id' => $discount->id,
            'code' => $discount->code,
            'value' => $discount->value,
            'type' => $discount->type,
            'min_order_price' => $discount->min_order_price,
            'start_date' => $discount->start_date,
            'end_date' => $discount->end_date,
        ];
    });

    return response()->json([
        'success' => true,
        'coupons' => $coupons
    ], 200);
}

}
