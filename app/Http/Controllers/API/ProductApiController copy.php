<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Facades\Assets;
use Botble\Base\Supports\Breadcrumb;
use Botble\Ecommerce\Enums\ProductTypeEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Forms\ProductForm;
use Botble\Ecommerce\Http\Requests\ProductRequest;
use Botble\Ecommerce\Models\GroupedProduct;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Productcategory;
use Botble\Ecommerce\Models\Brand;
use Botble\Ecommerce\Models\ProductVariation;
use Botble\Ecommerce\Models\ProductVariationItem;
use Botble\Ecommerce\Services\Products\DuplicateProductService;
use Botble\Ecommerce\Services\Products\StoreAttributesOfProductService;
use Botble\Ecommerce\Services\Products\StoreProductService;
use Botble\Ecommerce\Services\StoreProductTagService;
use Botble\Ecommerce\Tables\ProductTable;
use Botble\Ecommerce\Tables\ProductVariationTable;
use Botble\Ecommerce\Traits\ProductActionsTrait;
use Botble\Ecommerce\Models\Review;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Add this line
class ProductApiController extends Controller
{
    
         public function getAllProducts(Request $request)
            {
                // Log the request to ensure you're receiving the correct parameters
                // \Log::info($request->all());
                //   $userId = Auth::id();
                // $isUserLoggedIn = $userId !== null;
                // // Fetch the wishlist items for the logged-in user if they are authenticated
                // $wishlistProductIds = [];
                // if ($isUserLoggedIn) {
                //     $wishlistProductIds = \DB::table('ec_wish_lists')
                //         ->where('customer_id', $userId)
                //         ->pluck('product_id')
                //         ->toArray();
                // }

              
                // Get the logged-in user's ID
                $userId = Auth::id();
                $isUserLoggedIn = $userId !== null; // Check if the user is logged in
                
                // Log if the user is logged in or not
                Log::info('User logged in:', ['user_id' => $userId]);
                
                // Initialize an empty array to store product IDs in the wishlist
                $wishlistProductIds = [];
                // if ($isUserLoggedIn) {
                //     // Log before querying the wishlist
                //     Log::info('Fetching wishlist for user', ['user_id' => $userId]);
                
                //     // Fetch the product IDs in the user's wishlist
                //     $wishlistProductIds = DB::table('ec_wish_lists')
                //         ->where('customer_id', $userId)
                //         ->pluck('product_id')
                //         ->map(function($id) {
                //             return (int) $id; // Ensure all IDs are integers
                //         })
                //         ->toArray(); // Get all product IDs in the user's wishlist
                
                //     // Log the wishlist product IDs fetched
                //     Log::info('Wishlist product IDs:', ['wishlist_product_ids' => $wishlistProductIds]);
                // }
                
                                    // Check if user is logged in
                    if ($isUserLoggedIn) {
                        // Fetch wishlist items for logged-in user
                        $wishlistProductIds = DB::table('ec_wish_lists')
                            ->where('customer_id', $userId)
                            ->pluck('product_id')
                            ->map(function($id) {
                                return (int) $id; // Ensure all IDs are integers
                            })
                            ->toArray(); // Get all product IDs in the user's wishlist
                    } else {
                        // Handle guest wishlist (example using session)
                        $wishlistProductIds = session()->get('guest_wishlist', []); // Adjust based on your actual guest wishlist handling
                    }

                            
                // Start building the query
                $query = Product::with('categories', 'brand', 'tags', 'producttypes'); // Ensure 'categories' is included
            
                // Apply filters
                $this->applyFilters($query, $request);
            
                // Order by newest products first if no sorting is specified
               // Default sorting by 'created_at' if 'sort_by' is not present

                
                                // Log the final SQL query for debugging
                \Log::info($query->toSql());
                \Log::info($query->getBindings());
            
                     // Check if the user is authenticated
                     
                  // Get sort_by parameter
                    $sortBy = $request->input('sort_by', 'created_at'); // Defaults to 'created_at'
                
                    // Validate the sort_by option to avoid any SQL injection
                    $validSortOptions = ['created_at', 'price', 'name']; // Add other valid fields as needed
                    if (!in_array($sortBy, $validSortOptions)) {
                        $sortBy = 'created_at';
                    }
                
                            
                               // Build the query with the specified sort option or default to created_at
                $products = Product::orderBy($sortBy, 'asc')->get();
                // Get filtered product IDs
                $filteredProductIds = $query->pluck('id');
                
                   // Calculate min and max values for price, length, width, and height
                    $priceMin = Product::whereIn('id', $filteredProductIds)->min('sale_price');
                    $priceMax = Product::whereIn('id', $filteredProductIds)->max('sale_price');
                    $lengthMin = Product::whereIn('id', $filteredProductIds)->min('length');
                    $lengthMax = Product::whereIn('id', $filteredProductIds)->max('length');
                    $widthMin = Product::whereIn('id', $filteredProductIds)->min('width');
                    $widthMax = Product::whereIn('id', $filteredProductIds)->max('width');
                    $heightMin = Product::whereIn('id', $filteredProductIds)->min('height');
                    $heightMax = Product::whereIn('id', $filteredProductIds)->max('height');
                    // $DeliveryMin = Product::whereIn('id', $filteredProductIds)->min('delivery_days');
                    // $DeliveryMax = Product::whereIn('id', $filteredProductIds)->max('delivery_days');
                      $DeliveryMin = Product::whereNotNull('delivery_days')
                        ->selectRaw('MIN(CAST(delivery_days AS UNSIGNED)) as min_delivery_days')
                        ->value('min_delivery_days'); // This should return the correct minimum
                    
                    $DeliveryMax = Product::whereNotNull('delivery_days')
                        ->selectRaw('MAX(CAST(delivery_days AS UNSIGNED)) as max_delivery_days')
                        ->value('max_delivery_days'); // This should return the correct maximum


                // Subquery for best price and delivery date
                $subQuery = Product::select('sku')
                    ->selectRaw('MIN(price) as best_price')
                    ->selectRaw('MIN(delivery_days) as best_delivery_date')
                    ->whereIn('id', $filteredProductIds)
                    ->groupBy('sku');
            
                // Create the final products query while still respecting previous filters
                $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                    $join->on('ec_products.sku', '=', 'best_products.sku')
                        ->whereColumn('ec_products.price', 'best_products.best_price');
                })
                ->whereIn('id', $filteredProductIds) // Add the filtered IDs back to ensure all filters are respected
                ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
                ->with('reviews', 'currency', 'specifications') // Including necessary relationships
                ->paginate($request->input('per_page', 15)); // Pagination
            
                // Collect unique categories from products
                // $categories = $products->flatMap(function ($product) {
                //     return $product->categories; // This will give you a collection of categories
                // })->unique('id'); // Get unique categories by id
            
                $categories = ProductCategory::select('id', 'name')->get();
           
                    // Build the query with the specified sort option or defau

                // Collect brands
                $brands = Brand::select('id', 'name')->get();
              
            
                // Transform the result to include additional data
                $products->getCollection()->transform(function ($product) use ($wishlistProductIds){
                    // Add review and stock details
                    $totalReviews = $product->reviews->count();
                    $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
                    $quantity = $product->quantity ?? 0;
                    $unitsSold = $product->units_sold ?? 0;
                    $leftStock = $quantity - $unitsSold;
            
                    $product->total_reviews = $totalReviews;
                    $product->avg_rating = $avgRating;
                    $product->leftStock = $leftStock;
                    
                $product->in_wishlist = in_array($product->id, $wishlistProductIds); // Correct check
                                
            
                    // Add currency details
                    if ($product->currency) {
                        $product->currency_title = $product->currency->is_prefix_symbol
                            ? $product->currency->title
                            : $product->price . ' ' . $product->currency->title;
                    } else {
                        $product->currency_title = $product->price; // Fallback if no currency found
                    }
            
                    // Add specifications
                    if ($product->specs_sheet) {
                        $specifications = json_decode($product->specs_sheet, true);
                        $filteredSpecs = array_map(function($spec) {
                            return [
                                'spec_name' => $spec['spec_name'] ?? null,
                                'spec_value' => $spec['spec_value'] ?? null,
                            ];
                        }, $specifications);
                        $product->specifications = $filteredSpecs;
                    }
                    
                     // Check if the product is in the user's wishlist
             
                        
                    // Handle frequently bought together products
                    if ($product->frequently_bought_together) {
                        $frequentlyBoughtData = json_decode($product->frequently_bought_together, true);
                        $frequentlyBoughtSkus = array_column($frequentlyBoughtData, 'value');
            
                        $frequentlyBoughtProducts = Product::whereIn('sku', $frequentlyBoughtSkus)
                            ->with('reviews', 'currency') // Include reviews and currency in query
                            ->get();
            
                        // Enhance frequently bought products with reviews and currency
                        $frequentlyBoughtProducts->transform(function ($fbProduct) {
                            $totalReviews = $fbProduct->reviews->count();
                            $avgRating = $totalReviews > 0 ? $fbProduct->reviews->avg('star') : null;
            
                            $fbProduct->total_reviews = $totalReviews;
                            $fbProduct->avg_rating = $avgRating;
            
                            if ($fbProduct->currency) {
                                $fbProduct->currency_title = $fbProduct->currency->is_prefix_symbol
                                    ? $fbProduct->currency->title
                                    : $fbProduct->price . ' ' . $fbProduct->currency->title;
                            } else {
                                $fbProduct->currency_title = $fbProduct->price;
                            }
            
                            return $fbProduct;
                        });
            
                        $product->frequently_bought_together = $frequentlyBoughtProducts;
                    }
             
             
                             // Same SKU Different BRANDS

                //     $sameSkuProducts = Product::where('sku', $product->sku)
                //     ->where('id', '!=', $product->id) // Exclude the current product
                //     ->select('id', 'images') // Select the necessary fields
                //     ->get(); // Retrieve the results as a collection
                
                
                // // Same SKU BUT DIFFERENT BRANDS
                // $product->same_sku_product_ids = $sameSkuProducts->map(function ($item) {
                //     return [
                //         'id' => $item->id,
                //         'images' => $item->images, // Include images directly
                //     ];
                // });
                // $sameBrandSkuProducts = Product::where('sku', $product->sku)
                //                         ->where('id', '!=', $product->id) // Exclude current product
                //                         ->where('brand_id', $product->brand_id) // Filter by the same vendor
                //                         ->select('id', 'name','price','delivery_days','images') // Select only the id and images columns
                //                         ->get();
                
                // // Prepare the results
                // $product->sameBrandSkuProducts = $sameBrandSkuProducts->map(function ($item) {
                //     return [
                //         'id' => $item->id,
                //         'name' => $item->name,
                //         'price' => $item->price,
                //         'delivery_days' => $item->delivery_days,
                //         'images' => $item->images // Directly include images
                //     ];
                // });
                
                // Retrieve products with the same SKU, same brand, excluding the current product
                
                
                $sameSkuProducts = Product::where('sku', $product->sku)
                ->where('id', '!=', $product->id) // Exclude the current product
                ->select('id', 'name', 'price','delivery_days', 'images', 'currency_id') // Select necessary fields
                ->with('currency') // Eager load the currency relationship
                ->get(); // Retrieve the results as a collection
            
                // Prepare the results with additional details including currency information
                $product->same_sku_product_ids = $sameSkuProducts->map(function ($item) {
                    // Prepare currency title
                    $currencyTitle = $item->currency 
                        ? ($item->currency->is_prefix_symbol 
                            ? $item->currency->title 
                            : $item->price . ' ' . $item->currency->title)
                        : $item->price;
                
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'delivery_days'=>$item->delivery_days,
                        'images' => $item->images,
                        'currency_title' => $currencyTitle, // Include formatted currency title
                    ];
                });
                    
                 
                   $sameBrandSkuProducts = Product::where('sku', $product->sku)
                                        ->where('id', '!=', $product->id) // Exclude current product
                                        ->where('brand_id', $product->brand_id) // Filter by the same vendor
                                        ->select('id', 'name','images') // Select only the id and images columns
                                        ->get();
                
                // Prepare the results
                $product->sameBrandSkuProducts = $sameBrandSkuProducts->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'images' => $item->images // Directly include images
                    ];
                });

                        
                    // Handle compare products
                    if ($product->compare_products) {
                        $compareIds = json_decode($product->compare_products, true);
            
                        $compareProducts = Product::whereIn('id', $compareIds)
                            ->with('reviews', 'currency', 'specifications') // Include reviews and currency in query
                            ->get();
            
                        // Enhance compare products with reviews, currency, and specifications
                        $compareProducts->transform(function ($compareProduct) {
                            $totalReviews = $compareProduct->reviews->count();
                            $avgRating = $totalReviews > 0 ? $compareProduct->reviews->avg('star') : null;
            
                            $compareProduct->total_reviews = $totalReviews;
                            $compareProduct->avg_rating = $avgRating;
            
                            // Add currency details
                            if ($compareProduct->currency) {
                                $compareProduct->currency_title = $compareProduct->currency->is_prefix_symbol
                                    ? $compareProduct->currency->title
                                    : $compareProduct->price . ' ' . $compareProduct->currency->title;
                            } else {
                                $compareProduct->currency_title = $compareProduct->price;
                            }
            
                            // Add specifications
                            if ($compareProduct->specs_sheet) {
                                $specifications = json_decode($compareProduct->specs_sheet, true);
                                $filteredSpecs = array_map(function($spec) {
                                    return [
                                        'spec_name' => $spec['spec_name'] ?? null,
                                        'spec_value' => $spec['spec_value'] ?? null,
                                    ];
                                }, $specifications);
                                $compareProduct->specifications = $filteredSpecs;
                            }
            
                            return $compareProduct;
                        });
            
                        $product->compare_products = $compareProducts;
                    }
                    


            
                    // Add tags and types
                    $product->tags = $product->tags; // Assuming tags is a relationship in the Product model
                    $product->producttypes = $product->producttypes; // Assuming producttypes is a relationship in the Product model
            
                    return $product;
                });
            
                return response()->json([
                    'success' => true,
                    'data' => $products,
                    'brands' => $brands,
                    'categories' => $categories,
                    'price_min' => $priceMin,
                    'price_max' => $priceMax,
                    'length_min' => $lengthMin,
                    'length_max' => $lengthMax,
                    'width_min' => $widthMin,
                    'width_max' => $widthMax,
                    'height_min' => $heightMin,
                    'height_max' => $heightMax,
                    'delivery_min' =>  $DeliveryMin ,
                    'delivery_max' => $DeliveryMax ,
                ]);
         }
            
     
    public function getAllPublicProducts(Request $request)
            {
               

              
             
                            
                // Start building the query
                $query = Product::with('categories', 'brand', 'tags', 'producttypes'); // Ensure 'categories' is included
            
                // Apply filters
                $this->applyFilters($query, $request);
            
                // Order by newest products first if no sorting is specified
               // Default sorting by 'created_at' if 'sort_by' is not present

                
                                // Log the final SQL query for debugging
                \Log::info($query->toSql());
                \Log::info($query->getBindings());
            
                     // Check if the user is authenticated
                     
                  // Get sort_by parameter
                    $sortBy = $request->input('sort_by', 'created_at'); // Defaults to 'created_at'
                
                    // Validate the sort_by option to avoid any SQL injection
                    $validSortOptions = ['created_at', 'price', 'name']; // Add other valid fields as needed
                    if (!in_array($sortBy, $validSortOptions)) {
                        $sortBy = 'created_at';
                    }
                
                            
                               // Build the query with the specified sort option or default to created_at
                $products = Product::orderBy($sortBy, 'asc')->get();
                // Get filtered product IDs
                $filteredProductIds = $query->pluck('id');
                
                   // Calculate min and max values for price, length, width, and height
                    $priceMin = Product::whereIn('id', $filteredProductIds)->min('sale_price');
                    $priceMax = Product::whereIn('id', $filteredProductIds)->max('sale_price');
                    $lengthMin = Product::whereIn('id', $filteredProductIds)->min('length');
                    $lengthMax = Product::whereIn('id', $filteredProductIds)->max('length');
                    $widthMin = Product::whereIn('id', $filteredProductIds)->min('width');
                    $widthMax = Product::whereIn('id', $filteredProductIds)->max('width');
                    $heightMin = Product::whereIn('id', $filteredProductIds)->min('height');
                    $heightMax = Product::whereIn('id', $filteredProductIds)->max('height');
                    // $DeliveryMin = Product::whereIn('id', $filteredProductIds)->min('delivery_days');
                    // $DeliveryMax = Product::whereIn('id', $filteredProductIds)->max('delivery_days');
                      $DeliveryMin = Product::whereNotNull('delivery_days')
                        ->selectRaw('MIN(CAST(delivery_days AS UNSIGNED)) as min_delivery_days')
                        ->value('min_delivery_days'); // This should return the correct minimum
                    
                    $DeliveryMax = Product::whereNotNull('delivery_days')
                        ->selectRaw('MAX(CAST(delivery_days AS UNSIGNED)) as max_delivery_days')
                        ->value('max_delivery_days'); // This should return the correct maximum


                // Subquery for best price and delivery date
                $subQuery = Product::select('sku')
                    ->selectRaw('MIN(price) as best_price')
                    ->selectRaw('MIN(delivery_days) as best_delivery_date')
                    ->whereIn('id', $filteredProductIds)
                    ->groupBy('sku');
            
                // Create the final products query while still respecting previous filters
                $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                    $join->on('ec_products.sku', '=', 'best_products.sku')
                        ->whereColumn('ec_products.price', 'best_products.best_price');
                })
                ->whereIn('id', $filteredProductIds) // Add the filtered IDs back to ensure all filters are respected
                ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
                ->with('reviews', 'currency', 'specifications') // Including necessary relationships
                ->paginate($request->input('per_page', 15)); // Pagination
            
                // Collect unique categories from products
                // $categories = $products->flatMap(function ($product) {
                //     return $product->categories; // This will give you a collection of categories
                // })->unique('id'); // Get unique categories by id
            
                $categories = ProductCategory::select('id', 'name')->get();
           
                    // Build the query with the specified sort option or defau

                // Collect brands
                $brands = Brand::select('id', 'name')->get();
              
            
                // Transform the result to include additional data
                $products->getCollection()->transform(function ($product){
                    // Add review and stock details
                    $totalReviews = $product->reviews->count();
                    $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
                    $quantity = $product->quantity ?? 0;
                    $unitsSold = $product->units_sold ?? 0;
                    $leftStock = $quantity - $unitsSold;
            
                    $product->total_reviews = $totalReviews;
                    $product->avg_rating = $avgRating;
                    $product->leftStock = $leftStock;
                    
      
                                
            
                    // Add currency details
                    if ($product->currency) {
                        $product->currency_title = $product->currency->is_prefix_symbol
                            ? $product->currency->title
                            : $product->price . ' ' . $product->currency->title;
                    } else {
                        $product->currency_title = $product->price; // Fallback if no currency found
                    }
            
                    // Add specifications
                    if ($product->specs_sheet) {
                        $specifications = json_decode($product->specs_sheet, true);
                        $filteredSpecs = array_map(function($spec) {
                            return [
                                'spec_name' => $spec['spec_name'] ?? null,
                                'spec_value' => $spec['spec_value'] ?? null,
                            ];
                        }, $specifications);
                        $product->specifications = $filteredSpecs;
                    }
                    
                     // Check if the product is in the user's wishlist
             
                        
                    // Handle frequently bought together products
                    if ($product->frequently_bought_together) {
                        $frequentlyBoughtData = json_decode($product->frequently_bought_together, true);
                        $frequentlyBoughtSkus = array_column($frequentlyBoughtData, 'value');
            
                        $frequentlyBoughtProducts = Product::whereIn('sku', $frequentlyBoughtSkus)
                            ->with('reviews', 'currency') // Include reviews and currency in query
                            ->get();
            
                        // Enhance frequently bought products with reviews and currency
                        $frequentlyBoughtProducts->transform(function ($fbProduct) {
                            $totalReviews = $fbProduct->reviews->count();
                            $avgRating = $totalReviews > 0 ? $fbProduct->reviews->avg('star') : null;
            
                            $fbProduct->total_reviews = $totalReviews;
                            $fbProduct->avg_rating = $avgRating;
            
                            if ($fbProduct->currency) {
                                $fbProduct->currency_title = $fbProduct->currency->is_prefix_symbol
                                    ? $fbProduct->currency->title
                                    : $fbProduct->price . ' ' . $fbProduct->currency->title;
                            } else {
                                $fbProduct->currency_title = $fbProduct->price;
                            }
            
                            return $fbProduct;
                        });
            
                        $product->frequently_bought_together = $frequentlyBoughtProducts;
                    }
             
             
                             // Same SKU Different BRANDS

                //     $sameSkuProducts = Product::where('sku', $product->sku)
                //     ->where('id', '!=', $product->id) // Exclude the current product
                //     ->select('id', 'images') // Select the necessary fields
                //     ->get(); // Retrieve the results as a collection
                
                
                // // Same SKU BUT DIFFERENT BRANDS
                // $product->same_sku_product_ids = $sameSkuProducts->map(function ($item) {
                //     return [
                //         'id' => $item->id,
                //         'images' => $item->images, // Include images directly
                //     ];
                // });
                // $sameBrandSkuProducts = Product::where('sku', $product->sku)
                //                         ->where('id', '!=', $product->id) // Exclude current product
                //                         ->where('brand_id', $product->brand_id) // Filter by the same vendor
                //                         ->select('id', 'name','price','delivery_days','images') // Select only the id and images columns
                //                         ->get();
                
                // // Prepare the results
                // $product->sameBrandSkuProducts = $sameBrandSkuProducts->map(function ($item) {
                //     return [
                //         'id' => $item->id,
                //         'name' => $item->name,
                //         'price' => $item->price,
                //         'delivery_days' => $item->delivery_days,
                //         'images' => $item->images // Directly include images
                //     ];
                // });
                
                // Retrieve products with the same SKU, same brand, excluding the current product
                
                
                $sameSkuProducts = Product::where('sku', $product->sku)
                ->where('id', '!=', $product->id) // Exclude the current product
                ->select('id', 'name', 'price','delivery_days', 'images', 'currency_id') // Select necessary fields
                ->with('currency') // Eager load the currency relationship
                ->get(); // Retrieve the results as a collection
            
            // Prepare the results with additional details including currency information
            $product->same_sku_product_ids = $sameSkuProducts->map(function ($item) {
                // Prepare currency title
                $currencyTitle = $item->currency 
                    ? ($item->currency->is_prefix_symbol 
                        ? $item->currency->title 
                        : $item->price . ' ' . $item->currency->title)
                    : $item->price;
            
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'delivery_days'=>$item->delivery_days,
                    'images' => $item->images,
                    'currency_title' => $currencyTitle, // Include formatted currency title
                ];
            });
                 
                 
                   $sameBrandSkuProducts = Product::where('sku', $product->sku)
                                        ->where('id', '!=', $product->id) // Exclude current product
                                        ->where('brand_id', $product->brand_id) // Filter by the same vendor
                                        ->select('id', 'name','images') // Select only the id and images columns
                                        ->get();
                
                // Prepare the results
                $product->sameBrandSkuProducts = $sameBrandSkuProducts->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'images' => $item->images // Directly include images
                    ];
                });

                        
                    // Handle compare products
                    if ($product->compare_products) {
                        $compareIds = json_decode($product->compare_products, true);
            
                        $compareProducts = Product::whereIn('id', $compareIds)
                            ->with('reviews', 'currency', 'specifications') // Include reviews and currency in query
                            ->get();
            
                        // Enhance compare products with reviews, currency, and specifications
                        $compareProducts->transform(function ($compareProduct) {
                            $totalReviews = $compareProduct->reviews->count();
                            $avgRating = $totalReviews > 0 ? $compareProduct->reviews->avg('star') : null;
            
                            $compareProduct->total_reviews = $totalReviews;
                            $compareProduct->avg_rating = $avgRating;
            
                            // Add currency details
                            if ($compareProduct->currency) {
                                $compareProduct->currency_title = $compareProduct->currency->is_prefix_symbol
                                    ? $compareProduct->currency->title
                                    : $compareProduct->price . ' ' . $compareProduct->currency->title;
                            } else {
                                $compareProduct->currency_title = $compareProduct->price;
                            }
            
                            // Add specifications
                            if ($compareProduct->specs_sheet) {
                                $specifications = json_decode($compareProduct->specs_sheet, true);
                                $filteredSpecs = array_map(function($spec) {
                                    return [
                                        'spec_name' => $spec['spec_name'] ?? null,
                                        'spec_value' => $spec['spec_value'] ?? null,
                                    ];
                                }, $specifications);
                                $compareProduct->specifications = $filteredSpecs;
                            }
            
                            return $compareProduct;
                        });
            
                        $product->compare_products = $compareProducts;
                    }
                    

                    // Add tags and types
                    $product->tags = $product->tags; // Assuming tags is a relationship in the Product model
                    $product->producttypes = $product->producttypes; // Assuming producttypes is a relationship in the Product model
            
                    return $product;
                });
            
                return response()->json([
                    'success' => true,
                    'data' => $products,
                    'brands' => $brands,
                    'categories' => $categories,
                    'price_min' => $priceMin,
                    'price_max' => $priceMax,
                    'length_min' => $lengthMin,
                    'length_max' => $lengthMax,
                    'width_min' => $widthMin,
                    'width_max' => $widthMax,
                    'height_min' => $heightMin,
                    'height_max' => $heightMax,
                    'delivery_min' =>  $DeliveryMin ,
                    'delivery_max' => $DeliveryMax ,
                ]);
            }
            

        private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, \Illuminate\Http\Request $request)
        {
            // Log the request to ensure you're receiving the correct parameters
            \Log::info($request->all());
          \Log::info('Request Parameters:', $request->all());
            // Apply ID filter
            if ($request->has('id')) {
                $id = $request->input('id');
                $query->where('id', $id);
                \Log::info('Filter by ID: ' . $id);
            }
        
            // Search filters
            // if ($request->has('search')) {
            //     $searchTerm = $request->input('search');
            //     $query->where(function($q) use ($searchTerm) {
            //         $q->where('name', 'like', '%' . $searchTerm . '%')
            //           ->orWhere('sku', 'like', '%' . $searchTerm . '%');
            //     });
            // }
                    
                    // Search filters with category and brand
       
            // Search filter (product name or SKU)
            
            if ($request->has('search')) {
                $searchTerm = $request->input('search');
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('sku', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('categories', function($q) use ($searchTerm) {
                          $q->where('name', 'like', '%' . $searchTerm . '%');
                      })
                      ->orWhereHas('brand', function($q) use ($searchTerm) {
                          $q->where('name', 'like', '%' . $searchTerm . '%');
                      });
                });
            }
            
            
        
            if ($request->has('name')) {
                $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
            }
            
          



                
            if ($request->has('description')) {
                $query->where('description', 'LIKE', '%' . $request->input('description') . '%');
            }
        
            if ($request->has('content')) {
                $query->where('content', 'LIKE', '%' . $request->input('content') . '%');
            }
        
            // SKU filter
            if ($request->has('sku')) {
                $skus = $request->input('sku');
                if (is_array($skus)) {
                    $query->whereIn('sku', $skus);
                } else {
                    $query->where('sku', $skus);
                }
            }
        
            // Status filter
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }
        
            // Stock status filter
            if ($request->has('stock_status')) {
                $query->where('stock_status', $request->input('stock_status'));
            }
        
            // Product type filter
            if ($request->has('product_type')) {
                $query->where('product_type', $request->input('product_type'));
            }
            
         
        
            // Store ID filter
            if ($request->has('store_id')) {
                $query->where('store_id', $request->input('store_id'));
            }
        
            // Numerical filters
                // Delivery Days
            if ($request->has('delivery_days')) {
                $query->where('delivery_days', $request->input('delivery_days'));
            }
            if ($request->has('price_min')) {
                $query->where('price', '>=', $request->input('price_min'));
            }
        
            if ($request->has('price_max')) {
                $query->where('price', '<=', $request->input('price_max'));
            }
        
            if ($request->has('quantity_min')) {
                $query->where('quantity', '>=', $request->input('quantity_min'));
            }
        
            if ($request->has('quantity_max')) {
                $query->where('quantity', '<=', $request->input('quantity_max'));
            }
        
            // Date filters
            if ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->input('start_date'));
            }
        
            if ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->input('end_date'));
            }
        
            // Boolean filters
            if ($request->has('allow_checkout_when_out_of_stock')) {
                $query->where('allow_checkout_when_out_of_stock', $request->input('allow_checkout_when_out_of_stock'));
            }
        
            if ($request->has('with_storehouse_management')) {
                $query->where('with_storehouse_management', $request->input('with_storehouse_management'));
            }
        
            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->input('is_featured'));
            }
        
            if ($request->has('is_variation')) {
                $query->where('is_variation', $request->input('is_variation'));
            }
        
            // Variation filters
            if ($request->has('variant_grams')) {
                $query->where('variant_grams', $request->input('variant_grams'));
            }
        
            if ($request->has('variant_inventory_quantity')) {
                $query->where('variant_inventory_quantity', $request->input('variant_inventory_quantity'));
            }
        
            if ($request->has('variant_inventory_policy')) {
                $query->where('variant_inventory_policy', $request->input('variant_inventory_policy'));
            }
        
            if ($request->has('variant_fulfillment_service')) {
                $query->where('variant_fulfillment_service', $request->input('variant_fulfillment_service'));
            }
        
            if ($request->has('variant_requires_shipping')) {
                $query->where('variant_requires_shipping', $request->input('variant_requires_shipping'));
            }
        
            if ($request->has('variant_barcode')) {
                $query->where('variant_barcode', $request->input('variant_barcode'));
            }
        
            // Dimension filters
            if ($request->has('length_min')) {
                $query->where('length', '>=', $request->input('length_min'));
            }
        
            if ($request->has('length_max')) {
                $query->where('length', '<=', $request->input('length_max'));
            }
        
            if ($request->has('width_min')) {
                $query->where('width', '>=', $request->input('width_min'));
            }
        
            if ($request->has('width_max')) {
                $query->where('width', '<=', $request->input('width_max'));
            }
        
            if ($request->has('height_min')) {
                $query->where('height', '>=', $request->input('height_min'));
            }
        
            if ($request->has('height_max')) {
                $query->where('height', '<=', $request->input('height_max'));
            }
        
            // Weight filters
            if ($request->has('weight_min')) {
                $query->where('weight', '>=', $request->input('weight_min'));
            }
        
            if ($request->has('weight_max')) {
                $query->where('weight', '<=', $request->input('weight_max'));
            }
                              
            if ($request->has('rating')) {
                $rating = $request->input('rating');
                $query->whereHas('reviews', function($q) use ($rating) {
                    $q->selectRaw('product_id, AVG(star) as avg_rating') // Include product_id in the select statement
                      ->groupBy('product_id')
                      ->havingRaw('AVG(star) = ?', [$rating]); // Change from >= to =
                });
            }

                    // if ($request->has('brand_id')) {
                    //     $brandIds = $request->input('brand_id');
                    
                    //     // Convert to array if needed
                    //     if (!is_array($brandIds)) {
                    //         $brandIds = explode(',', $brandIds);
                    //     }
                    
                    //     \Log::info('Filtering by Brand IDs: ', $brandIds);
                    
                    //     // Apply filter on the existing query object
                    //     $query->whereIn('brand_id', $brandIds);
                    // }
                    
                    if ($request->has('brand_id')) {
                        $brandIds = $request->input('brand_id');
                        
                        // Convert to array if needed
                        if (!is_array($brandIds)) {
                            $brandIds = explode(',', $brandIds);
                        }
                    
                        // Ensure brand IDs are integers
                        $brandIds = array_map('intval', $brandIds);
                    
                        \Log::info('Filtering by Brand IDs: ', $brandIds);
                    
                        // Apply filter on the existing query object
                        $query->whereIn('brand_id', $brandIds);
                    }
                    // Continue with any other filters or sorting options


        
            // Brand filter by name
            if ($request->has('brand_names')) {
                $brandNames = $request->input('brand_names');
        
                // Check if $brandNames is an array
                if (is_array($brandNames)) {
                    // Fetch brand IDs based on names
                    $brandIds = Brand::whereIn('name', $brandNames)->pluck('id');
                    
                    // Apply the filter using brand IDs
                    $query->whereIn('brand_id', $brandIds);
                } else {
                    // If it's a single name, convert it into an array
                    $brandIds = Brand::where('name', $brandNames)->pluck('id');
                    $query->whereIn('brand_id', $brandIds);
                }
            }
            
                     // Sort by price if specified, else default to the general `sort_by` handling
            if ($request->has('sort_by_price')) {
                $order = strtolower($request->input('sort_by_price')); // Normalize input
                if (in_array($order, ['asc', 'desc'])) {
                    $query->orderBy('sale_price', $order);
                    \Log::info("Sorting by price in $order order");
                } else {
                    \Log::info("Invalid sort_by_price parameter: $order");
                }
            } else {
                // General sorting by other columns
                $allowedSortBy = ['id', 'price', 'created_at', 'name'];
                $sortBy = $request->input('sort_by', 'id');
                $sortOrder = strtolower($request->input('sort_order', 'asc'));
                
                if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, ['asc', 'desc'])) {
                    $query->orderBy($sortBy, $sortOrder);
                    \Log::info("Sorting by: $sortBy in $sortOrder order");
                } else {
                    \Log::info("Invalid sort parameters: sort_by = $sortBy, sort_order = $sortOrder");
                }
            }
            
             //$products = $query->orderBy($sortBy, 'asc')->paginate($request->input('per_page', 15)); // Pagination

                        //  $products = $query->orderBy($sortBy, 'asc'); // Pagination
        
        
            // Log the final SQL query for debugging
            \Log::info($query->toSql());
            \Log::info($query->getBindings());
        }
        














}
