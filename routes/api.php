<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\ProductApiController;
use App\Http\Controllers\API\BrandApiController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\SimpleSliderController;
use App\Http\Controllers\API\SimpleSliderItemController;
use App\Http\Controllers\API\CategoryApiController;
use App\Http\Controllers\API\ReviewsApiController;
use App\Http\Controllers\API\DiscountsApiController;
use App\Http\Controllers\API\CartApiController;
use App\Http\Controllers\API\PostApiController;
use App\Http\Controllers\API\PostCategoryController;
use App\Http\Controllers\API\CartTotalApiController;
use App\Http\Controllers\API\WishlistApiController;
use App\Http\Controllers\API\CartMultipleProductsApiController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\API\OrderApiController;
use App\Http\Controllers\API\PaymentApiController;
use App\Http\Controllers\API\WishlistCountApiController;
use App\Http\Controllers\API\CouponApiController;
use App\Http\Controllers\API\SearchApiController;
use App\Http\Controllers\API\CartSummaryController;
use App\Http\Controllers\API\RecentlyViewedProductController;
use App\Http\Controllers\API\UserReviewApiController;
use App\Http\Controllers\API\CustomerCouponApiController;
use App\Http\Controllers\API\ForgotPasswordApiController;
use App\Http\Controllers\API\ApiResetPasswordController;
use App\Http\Controllers\API\SaveForLaterController;
use App\Http\Controllers\CartController; // Adjust if the controller name is different
use App\Http\Controllers\API\CountryController;
 use App\Http\Controllers\API\OrderTrackingController;
 use App\Http\Controllers\API\AddressController;
 use App\Http\Controllers\API\PopularPostsController;
 use App\Http\Controllers\API\SquarePaymentController;

 Route::post('/payment-square', [SquarePaymentController::class, 'processPayment']);
 


 Route::get('/popular-posts', [PopularPostsController::class, 'index']);

 
Route::get('/order-tracking', [OrderTrackingController::class, 'trackOrder']);

Route::middleware(['auth:sanctum'])->prefix('addresses')->group(function () {
    Route::get('/', [AddressController::class, 'index']);
    Route::post('/', [AddressController::class, 'store']);
    Route::put('/{id}', [AddressController::class, 'update']);
    Route::delete('/{id}', [AddressController::class, 'destroy']);
 Route::post('/update-default-address', [AddressController::class, 'updateDefaultAddress']);
    
});
Route::post('/upload-product-documents', [ProductController::class, 'uploadDocuments']);



Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/{id}', [CountryController::class, 'show']);

Route::post('password/reset', [ApiResetPasswordController::class, 'reset'])->name('api.password.reset');


Route::post('/forgot-password', [ForgotPasswordApiController::class, 'sendResetLinkEmail']);


Route::middleware('auth:sanctum')->get('/customer-reviews', [UserReviewApiController::class, 'getCustomerReviews']);
Route::middleware('auth:sanctum')->post('/add-customer-reviews', [UserReviewApiController::class, 'createReview']);
Route::middleware('auth:sanctum')->put('/customer-reviews-update/{id}', [UserReviewApiController::class, 'updateReview']);
Route::middleware('auth:sanctum')->delete('/customer-reviews-delete/{id}', [UserReviewApiController::class, 'deleteReview']);


Route::middleware('auth:sanctum')->post('/save-for-later', [SaveForLaterController::class, 'saveForLater']);
Route::middleware('auth:sanctum')->get('/show-save-for-later', [SaveForLaterController::class, 'showSaveForLater']);
Route::middleware('auth:sanctum')->post('/remove-from-save-for-later', [SaveForLaterController::class, 'removeFromSaveForLater']);



Route::middleware('auth:sanctum')->get('/customer/coupons', [CustomerCouponApiController::class, 'getCustomerCoupons']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('recently-viewed', [RecentlyViewedProductController::class, 'addToRecent']);
    Route::get('recently-viewed', [RecentlyViewedProductController::class, 'getRecentProducts']);
});

Route::post('/create-payment', [PaymentApiController::class, 'createPayment']);
Route::get('/payment/success', [PaymentApiController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/cancel', [PaymentApiController::class, 'paymentCancel'])->name('payment.cancel');

Route::get('/search', [SearchApiController::class, 'search']);


Route::get('/location', [LocationController::class, 'getLocation']);
Route::get('categories/{id}/products', [CategoryController::class, 'getProductsByCategory']);
Route::get('categories/filters', [CategoryController::class, 'getSpecificationFilters']);
Route::post('categories/specification-filters', [CategoryController::class, 'getSpecificationFilters']);
Route::post('categories/filtered-products', [CategoryController::class, 'getFilteredProducts']);
Route::get('/categories/{slug}', [CategoryController::class, 'categoryslug']);

Route::prefix('categories')->group(function () {
    
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'index']);
    Route::put('{id}', [CategoryController::class, 'update']);
    Route::delete('{id}', [CategoryController::class, 'destroy']);
    Route::get('{id}', [CategoryController::class, 'show']);
    //Route::middleware('auth:sanctum')->get('/', [CategoryController::class, 'index']);
   // Route::middleware('auth:sanctum')->post('/', [CategoryController::class, 'store']);
    //Route::middleware('auth:sanctum')->put('{id}', [CategoryController::class, 'update']);
    //Route::middleware('auth:sanctum')->delete('{id}', [CategoryController::class, 'destroy']);
    //Route::middleware('auth:sanctum')->get('{id}', [CategoryController::class, 'show']);
    // Route::get('/', [CategoryController::class, 'index']);
    // Route::get('{id}', [CategoryController::class, 'show']);
    // Route::post('/', [CategoryController::class, 'store']);
    // Route::put('{id}', [CategoryController::class, 'update']);
    // Route::delete('{id}', [CategoryController::class, 'destroy']);
});


Route::prefix('/simple-slider')->group(function () {
    Route::get('/', [SimpleSliderController::class, 'index']);
    Route::post('/', [SimpleSliderController::class, 'store']);
    Route::get('{id}', [SimpleSliderController::class, 'show']);
    Route::put('{id}', [SimpleSliderController::class, 'update']);
    Route::delete('{id}', [SimpleSliderController::class, 'destroy']);
});

Route::post('simple-slider-items', [SimpleSliderItemController::class, 'store']);
Route::get('/menus', [MenuController::class, 'index']);
Route::get('/menus/{id}', [MenuController::class, 'show']);
Route::post('/menus', [MenuController::class, 'store']);
Route::put('/menus/{id}', [MenuController::class, 'update']);
Route::delete('/menus/{id}', [MenuController::class, 'destroy']);


Route::post('/register', [CustomerController::class, 'register']);
Route::post('/login', [CustomerController::class, 'login']);

Route::get('/customers', [CustomerController::class, 'index']);
//  Route::get('/products', [ProductApiController::class, 'getAllProducts']);
 Route::get('/brandguestproducts', [BrandApiController::class, 'getAllBrandGuestProducts']);

Route::middleware('auth:sanctum')->get('/brandproducts', [BrandApiController::class, 'getAllBrandProducts']);

Route::middleware('auth:sanctum')->get('/categoryproducts', [CategoryApiController::class, 'getAllFeaturedProductsByCategory']);

Route::get('/categoryguestproducts', [CategoryApiController::class, 'getAllGuestFeaturedProductsByCategory']);
Route::get('/reviews', [ReviewsApiController::class, 'getProductReviews']);
Route::get('/product-discounts', [DiscountsApiController::class, 'getDiscountsForProduct']);
// Route::put('/profile', [CustomerController::class, 'updateProfile']);
// Route::get('/profile', [CustomerController::class, 'getProfile']);
//Route::middleware('auth:sanctum')->get('/products', [ProductApiController::class, 'getAllProducts']);
// Route::middleware('auth:sanctum')->post('/logout', [CustomerController::class, 'logout']);


//Route::middleware('auth:sanctum')->get('/products', [ProductApiController::class, 'getAllProducts']);
//Route::middleware('auth:sanctum')->post('/login', [CustomerController::class, 'login']);
Route::middleware('auth:sanctum')->put('/profile', [CustomerController::class, 'updateProfile']);
Route::middleware('auth:sanctum')->get('/profile', [CustomerController::class, 'getProfile']);
//Route::middleware('auth:sanctum')->get('/products', [ProductApiController::class, 'getAllProducts']);
Route::middleware('auth:sanctum')->post('/logout', [CustomerController::class, 'logout']);


// Public route (accessible by both authenticated and non-authenticated users)

// Accessible to both authenticated and non-authenticated users


// Authenticated route (only accessible by authenticated users)
// Route::middleware('auth:sanctum')->get('/products', [ProductApiController::class, 'getAllProducts']);
    Route::get('/products-guest', [ProductApiController::class, 'getAllPublicProducts']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/apply-coupon', [CouponApiController::class, 'applyCoupon']);

    Route::get('/products', [ProductApiController::class, 'getAllProducts']);
    Route::get('/product-listing', [ProductApiController::class, 'getAllProductsLising']);
    Route::get('/product-public-listing-guest', [ProductApiController::class, 'getAllProductsLisingGuest']);
    
    
    // Routes for logged-in users
    Route::post('/cart', [CartApiController::class, 'addToCart']);
    Route::get('/cart', [CartApiController::class, 'viewCart']);
    Route::delete('/cart', [CartApiController::class, 'clearCart']);
    Route::put('/cart/decrease', [CartApiController::class, 'decreaseQuantity']); // Decrease quantity for logged-in users
    Route::delete('/cart/{productId}', [CartApiController::class, 'clearProductFromCart']); // Decrease quantity for logged-in users
    // Route for updating cart quantity (both user and guest versions)
Route::post('/cart/update', [CartApiController::class, 'updateCartQuantity']);
  Route::get('/cart/total', [CartTotalApiController::class, 'totalProductsInCart']);
   // Route::post('/cart/multiple-add', [CartMultipleProductsApiController::class, 'addMultipleToCart']);

Route::post('/cart/multiple', [CartMultipleProductsApiController::class, 'addMultipleToCart']);

Route::get('/cart-summary', [CartSummaryController::class, 'cartSummary']);


});

Route::post('/cart/guest/multiple', [CartMultipleProductsApiController::class, 'addMultipleToCart']);

// Routes for guest users
Route::post('/cart/guest', [CartApiController::class, 'addToCartGuest']);
Route::get('/cart/guest', [CartApiController::class, 'viewCartGuest']);
Route::delete('/cart/guest', [CartApiController::class, 'clearCartGuest']);
Route::patch('/cart/guest/decrease', [CartApiController::class, 'decreaseQuantityGuest']); // Decrease quantity for guest users
Route::post('/cart/update-guest', [CartApiController::class, 'updateQuantityGuest']);
 Route::get('/cart/total/guest', [CartTotalApiController::class, 'totalProductsInCartGuest']);


//     // Add item to guest cart
//     Route::post('/cart/add-to-cart-guest', [CartApiController::class, 'addToCartGuest'])->name('cart.add.guest');

//     // View guest cart
//     Route::get('/view-cart-guest', [CartApiController::class, 'viewCartGuest'])->name('cart.view.guest');

//     // Update guest cart quantity (decrease)
//     Route::post('/decrease-quantity', [CartApiController::class, 'decreaseQuantity'])->name('cart.decrease.guest');

//     // Clear guest cart
//     Route::delete('/clear-cart-guest', [CartApiController::class, 'clearCartGuest'])->name('cart.clear.guest');


// Routes for Blog Posts 
Route::get('/posts', [PostApiController::class, 'index']);
Route::get('/get-views', [PostApiController::class, 'getlikes']);
Route::get('/posts/{id}', [PostApiController::class, 'show']);

Route::put('/posts/{id}', [PostApiController::class, 'update']);
Route::put('/post-comments/{id}', [PostApiController::class, 'postComment']);
Route::get('/postcategories', [PostCategoryController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/wishlist/add', [WishlistApiController::class, 'addToWishlist']);
    Route::get('/wishlist', [WishlistApiController::class, 'getWishlist']);
    Route::delete('/wishlist/remove', [WishlistApiController::class, 'removeFromWishlist']); // No productId in the URL
    Route::get('/wishlist/count', [WishlistCountApiController::class, 'getWishlistCount']);

});

// Routes for guest users
Route::middleware('web')->group(function () {

});

    Route::post('wishlist/guest', [WishlistApiController::class, 'addToWishlist']);
    Route::get('wishlist/guest', [WishlistApiController::class, 'getWishlist']);
    //Route::delete('wishlist/guest/{id}', [WishlistApiController::class, 'removeFromWishlist']);
    Route::delete('/wishlist/guest/remove', [WishlistApiController::class, 'removeFromWishlist']); // No productId in the URL





    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderApiController::class, 'index']);
        Route::post('/orders', [OrderApiController::class, 'store']);
        Route::get('/orders/{id}', [OrderApiController::class, 'show']);
        Route::put('/orders/{id}', [OrderApiController::class, 'update']);
        Route::delete('/orders/{id}', [OrderApiController::class, 'destroy']);
    });
