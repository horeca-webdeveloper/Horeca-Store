<?php 

// namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
// use Botble\Ecommerce\Models\Review; // Assuming the review model is located here
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;

// class UserReviewApiController  extends Controller
// {
//     /**
//      * Get all reviews for the logged-in customer
//      *
//      * @return \Illuminate\Http\JsonResponse
//      */
//     public function getCustomerReviews()
//     {
//         $userId = Auth::id(); // Get the authenticated user

//         if (!$userId) {
//             return response()->json(['message' => 'User not authenticated.'], 401);
//         }

//         // Fetch reviews for the logged-in customer
//         $reviews = Review::where('customer_id', $userId)
//             ->with('product') // Eager load product details
//             ->get(); // You can also paginate if needed

//         // Check if reviews exist
//         if ($reviews->isEmpty()) {
//             return response()->json(['message' => 'No reviews found for this user.'], 404);
//         }

//         // Return reviews with product data
//         return response()->json($reviews);
//     }
// }




namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Review; // Assuming the review model is located here
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserReviewApiController extends Controller
{
    /**
     * Get all reviews for the logged-in customer
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerReviews()
    {
        $userId = Auth::id(); // Get the authenticated user

        if (!$userId) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        // Fetch reviews for the logged-in customer
        $reviews = Review::where('customer_id', $userId)
            ->with('product') // Eager load product details
            ->get(); // You can also paginate if needed

        // Check if reviews exist
        if ($reviews->isEmpty()) {
            return response()->json(['message' => 'No reviews found for this user.'], 404);
        }

        // Return reviews with product data
        return response()->json($reviews);
    }

  

public function createReview(Request $request)
{
    $userId = Auth::id();

    if (!$userId) {
        return response()->json(['message' => 'User not authenticated.', 'success' => false], 401);
    }

    // Validate request
    $request->validate([
        'product_id' => 'required|exists:ec_products,id',
        'star' => 'required|integer|min:1|max:5',
        'comment' => 'required|string',
        'images' => 'nullable|array',
        'images.*' => 'file|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    // Check if the user already submitted a review for this product
    $existingReview = Review::where('customer_id', $userId)
        ->where('product_id', $request->product_id)
        ->first();

    if ($existingReview) {
        return response()->json([
            'message' => 'You have already submitted a review for this product.',
            'success' => true,
            'review' => $existingReview
        ], 200);
    }

    // Handle file uploads
    $imageUrls = [];
    try {
        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->storeAs('public/storage/products', $image->getClientOriginalName());
                $imageUrls[] = asset("storage/products/{$image->getClientOriginalName()}");
            }
        }

        // Create the review
        $review = Review::create([
            'customer_id' => $userId,
            'customer_name' => Auth::user()->name,
            'product_id' => $request->product_id,
            'star' => $request->star,
            'comment' => $request->comment,
            'status' => 'pending',
            'images' => !empty($imageUrls) ? json_encode($imageUrls) : null,
        ]);

        if ($review) {
            return response()->json([
                'message' => 'Review successfully added',
                'success' => true,
                'review' => $review,
            ], 201);
        }

        return response()->json(['message' => 'Review failed', 'success' => false], 500);

    } catch (\Exception $e) {
        return response()->json(['message' => 'Error occurred: ' . $e->getMessage(), 'success' => false], 500);
    }
}


    /**
     * Update a specific review
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    // public function updateReview(Request $request, $id)
    // {
    //     $userId = Auth::id();

    //     if (!$userId) {
    //         return response()->json(['message' => 'User not authenticated.'], 401);
    //     }

    //     // Find the review by ID
    //     $review = Review::where('id', $id)->where('customer_id', $userId)->first();

    //     if (!$review) {
    //         return response()->json(['message' => 'Review not found or unauthorized.'], 404);
    //     }

    //     // Validate the incoming request
    //     $request->validate([
    //         'star' => 'nullable|integer|min:1|max:5',
    //         'comment' => 'nullable|string',
    //         'images' => 'nullable|array',
    //         'images.*' => 'url', // Ensure each image is a valid URL
    //     ]);

    //     // Update the review
    //     $review->update($request->only(['star', 'comment', 'images']));

    //     return response()->json(['message' => 'Review updated successfully.', 'review' => $review]);
    // }
    
    /**
 * Update a specific review
 *
 * @param \Illuminate\Http\Request $request
 * @param int $id
 * @return \Illuminate\Http\JsonResponse
 */

    public function updateReview(Request $request, $id)
    {
        $userId = Auth::id(); // Get the authenticated user's ID

        if (!$userId) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        // Find the review by ID
        $review = Review::where('id', $id)->where('customer_id', $userId)->first();

        if (!$review) {
            return response()->json(['message' => 'Review not found or unauthorized.'], 404);
        }

        // Validate incoming request
        $request->validate([
            'star' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $uploadedImagePaths = [];
        
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = uniqid() . '_' . $image->getClientOriginalName();
                $image->move(public_path('storage'), $filename);

                $uploadedImagePaths[] = asset('storage/' . $filename);
            }
        }

        $dataToUpdate = $request->only(['star', 'comment']);
        if (!empty($uploadedImagePaths)) {
            $dataToUpdate['images'] = $uploadedImagePaths;
        }

        $review->update($dataToUpdate);

        return response()->json([
            'message' => 'Review updated successfully.',
            'review' => $review,
        ]);
    }




    /**
     * Delete a specific review
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteReview($id)
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        // Find the review by ID
        $review = Review::where('id', $id)->where('customer_id', $userId)->first();

        if (!$review) {
            return response()->json(['message' => 'Review not found or unauthorized.'], 404);
        }

        // Delete the review
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully.']);
    }
}
