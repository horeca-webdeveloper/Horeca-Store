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

class ProductApiController extends Controller
{
    
         public function getAllProducts(Request $request)
            {
                // Log the request to ensure you're receiving the correct parameters
                \Log::info($request->all());
                  $userId = Auth::id();
                $isUserLoggedIn = $userId !== null;
                // Fetch the wishlist items for the logged-in user if they are authenticated
                $wishlistProductIds = [];
                if ($isUserLoggedIn) {
                    $wishlistProductIds = \DB::table('ec_wish_list')
                        ->where('customer_id', $userId)
                        ->pluck('product_id')
                        ->toArray();
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
               $product->in_wishlist = in_array($product->id, $wishlistProductIds);
                                
                        
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
                    
//                     if ($product->compare_products) {
//     $compareIds = json_decode($product->compare_products, true);

//     $compareProducts = Product::whereIn('id', $compareIds)
//         ->with('reviews', 'currency') // Include reviews and currency in query
//         ->get();

//     // Step 1: Define the fixed order of specification names
//     $fixedSpecOrder = [
//         'Manufacturer',
//         'Model Number',
//         'Shipping Weight',
//         'Width',
//         'Depth',
//         'Height',
//         'Amps',
//         'Hertz',
//         'Phase',
//         'Voltage',
//         'Capacity',
//         'Casters',
//         'Compressor Location',
//         'Door style',
//         'Door Type'
//     ];

//     // Step 2: Prepare to collect specifications
//     $specData = [];
//     $uniqueSpecNames = [];

//     // Step 3: Collect specifications from each compare product
//     foreach ($compareProducts as $compareProduct) {
//         if ($compareProduct->specs_sheet) {
//             $specifications = json_decode($compareProduct->specs_sheet, true);
//             foreach ($specifications as $spec) {
//                 $specName = $spec['spec_name'] ?? null;
//                 $specValue = $spec['spec_value'] ?? 'N/A';

//                 if ($specName) {
//                     // Store unique spec names
//                     if (!in_array($specName, $uniqueSpecNames)) {
//                         $uniqueSpecNames[] = $specName;
//                     }
//                     // Map specifications to their values
//                     $specData[$compareProduct->id][$specName] = $specValue;
//                 }
//             }
//         }
//     }

//     // Step 4: Prepare structured specification arrays in fixed order
//     foreach ($compareProducts as $compareProduct) {
//         $totalReviews = $compareProduct->reviews->count();
//         $avgRating = $totalReviews > 0 ? $compareProduct->reviews->avg('star') : null;

//         $compareProduct->total_reviews = $totalReviews;
//         $compareProduct->avg_rating = $avgRating;

//         // Add currency details
//         if ($compareProduct->currency) {
//             $compareProduct->currency_title = $compareProduct->currency->is_prefix_symbol
//                 ? $compareProduct->currency->title
//                 : $compareProduct->price . ' ' . $compareProduct->currency->title;
//         } else {
//             $compareProduct->currency_title = $compareProduct->price;
//         }

//         // Step 5: Create specifications in the fixed order
//         $specifications = []; // Temporary array to hold specifications
//         foreach ($fixedSpecOrder as $specName) {
//             $specifications[] = [
//                 'spec_name' => $specName,
//                 'spec_value' => $specData[$compareProduct->id][$specName] ?? 'N/A', // Get value or default to 'N/A'
//             ];
//         }

//         // Assign the structured specifications back to the compare product
//         $compareProduct->setAttribute('specifications', $specifications);
//     }

//     // Step 6: Assign the enhanced compare products back to the original product
//     $product->compare_products = $compareProducts;
// }

            
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
        

//     public function getAllProducts(Request $request)
//     {
        
//          // Log the request to ensure you're receiving the correct parameters
//     \Log::info($request->all());
//         // Start building the query
//       //  $query = Product::query();
//   $query = \DB::table('ec_products');
//       if ($request->has('id')) {
//         $id = $request->input('id');
//         $product = \DB::select('SELECT * FROM ec_products WHERE id = ?', [$id]);

//         \Log::info('Product: ', (array)$product); // Log the fetched product

//         if ($product) {
//             return response()->json($product); // Return the product if found
//         } else {
//             return response()->json(['message' => 'Product not found'], 404);
//         }
//     }
//     // Start with a query for all products
 


//         // Apply filters
//         if ($request->has('search')) {
//             $query->where('name', 'like', '%' . $request->input('search') . '%');
//         }

//         if ($request->has('name')) {
//             $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
//         }

//         if ($request->has('description')) {
//             $query->where('description', 'LIKE', '%' . $request->input('description') . '%');
//         }

//         if ($request->has('content')) {
//             $query->where('content', 'LIKE', '%' . $request->input('content') . '%');
//         }

//         if ($request->has('sku')) {
//             $query->where('sku', $request->input('sku'));
//         }

//         if ($request->has('status')) {
//             $query->where('status', $request->input('status'));
//         }

//         if ($request->has('stock_status')) {
//             $query->where('stock_status', $request->input('stock_status'));
//         }

//         if ($request->has('product_type')) {
//             $query->where('product_type', $request->input('product_type'));
//         }

//         if ($request->has('store_id')) {
//             $query->where('store_id', $request->input('store_id'));
//         }

//         // Numerical filters
//         if ($request->has('price_min')) {
//             $query->where('price', '>=', $request->input('price_min'));
//         }

//         if ($request->has('price_max')) {
//             $query->where('price', '<=', $request->input('price_max'));
//         }

//         if ($request->has('quantity_min')) {
//             $query->where('quantity', '>=', $request->input('quantity_min'));
//         }

//         if ($request->has('quantity_max')) {
//             $query->where('quantity', '<=', $request->input('quantity_max'));
//         }

//         // Date filters
//         if ($request->has('start_date')) {
//             $query->where('created_at', '>=', $request->input('start_date'));
//         }

//         if ($request->has('end_date')) {
//             $query->where('created_at', '<=', $request->input('end_date'));
//         }

//         // Boolean filters
//         if ($request->has('allow_checkout_when_out_of_stock')) {
//             $query->where('allow_checkout_when_out_of_stock', $request->input('allow_checkout_when_out_of_stock'));
//         }

//         if ($request->has('with_storehouse_management')) {
//             $query->where('with_storehouse_management', $request->input('with_storehouse_management'));
//         }

//         if ($request->has('is_featured')) {
//             $query->where('is_featured', $request->input('is_featured'));
//         }

//         if ($request->has('is_variation')) {
//             $query->where('is_variation', $request->input('is_variation'));
//         }

//         // Variation filters
//         if ($request->has('variant_grams')) {
//             $query->where('variant_grams', $request->input('variant_grams'));
//         }

//         if ($request->has('variant_inventory_quantity')) {
//             $query->where('variant_inventory_quantity', $request->input('variant_inventory_quantity'));
//         }

//         if ($request->has('variant_inventory_policy')) {
//             $query->where('variant_inventory_policy', $request->input('variant_inventory_policy'));
//         }

//         if ($request->has('variant_fulfillment_service')) {
//             $query->where('variant_fulfillment_service', $request->input('variant_fulfillment_service'));
//         }

//         if ($request->has('variant_requires_shipping')) {
//             $query->where('variant_requires_shipping', $request->input('variant_requires_shipping'));
//         }

//         if ($request->has('variant_barcode')) {
//             $query->where('variant_barcode', $request->input('variant_barcode'));
//         }

//         // Dimension filters
//         if ($request->has('length_min')) {
//             $query->where('length', '>=', $request->input('length_min'));
//         }

//         if ($request->has('length_max')) {
//             $query->where('length', '<=', $request->input('length_max'));
//         }

//         if ($request->has('width_min')) {
//             $query->where('width', '>=', $request->input('width_min'));
//         }

//         if ($request->has('width_max')) {
//             $query->where('width', '<=', $request->input('width_max'));
//         }

//         if ($request->has('height_min')) {
//             $query->where('height', '>=', $request->input('height_min'));
//         }

//         if ($request->has('height_max')) {
//             $query->where('height', '<=', $request->input('height_max'));
//         }

//         // Weight filters
//         if ($request->has('weight_min')) {
//             $query->where('weight', '>=', $request->input('weight_min'));
//         }

//         if ($request->has('weight_max')) {
//             $query->where('weight', '<=', $request->input('weight_max'));
//         }

//         // Rating filter
//         if ($request->has('rating')) {
//             $rating = $request->input('rating');
//             $query->whereHas('reviews', function($q) use ($rating) {
//                 $q->selectRaw('AVG(star) as avg_rating')
//                   ->groupBy('product_id')
//                   ->havingRaw('AVG(star) >= ?', [$rating]);
//             });
//         }

//         // Brand filter
//         if ($request->has('brand_id')) {
//             $query->where('brand_id', $request->input('brand_id'));
//         }
//         \Log::info('Product ID: ' . $request->id); // Log the ID being passed

// \Log::info($query->toSql()); // Logs the raw SQL query being generated

//       $products = $query->paginate(10); // Or use ->get() if you don't want pagination

//         // Subquery for best price and delivery date
//         $filteredProductIds = $query->pluck('id');
//         $subQuery = Product::select('sku')
//             ->selectRaw('MIN(price) as best_price')
//             ->selectRaw('MIN(delivery_date) as best_delivery_date')
//             ->whereIn('id', $filteredProductIds)
//             ->groupBy('sku');

//         // Main query with left join on subquery
//         $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
//             $join->on('ec_products.sku', '=', 'best_products.sku')
//                  ->whereColumn('ec_products.price', 'best_products.best_price');
//         })
//         ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
//         ->with('reviews', 'currency')
//         ->paginate($request->input('per_page', 15));

//         // Transform the result to include additional data
//         $products->getCollection()->transform(function ($product) {
//             $totalReviews = $product->reviews->count();
//             $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
//             $quantity = $product->quantity ?? 0;
//             $unitsSold = $product->units_sold ?? 0;
//             $leftStock = $quantity - $unitsSold;

//             $product->total_reviews = $totalReviews;
//             $product->avg_rating = $avgRating;
//             $product->leftStock = $leftStock;

//             if ($product->currency) {
//                 $product->currency_title = $product->currency->is_prefix_symbol
//                     ? $product->currency->title
//                     : $product->price . ' ' . $product->currency->title;
//             } else {
//                 $product->currency_title = $product->price; // Fallback if no currency found
//             }

//             return $product;
//         });

//         return response()->json([
//             'success' => true,
//             'data' => $products
//         ]);
//     }





// public function getAllProducts(Request $request)
// {
//             // Log the request to ensure you're receiving the correct parameters
//             \Log::info($request->all());

//             // Start building the query
//         // $query = Product::query(); // Use Eloquent model instead of DB facade for left joins
//             $query = Product::with('category', 'brand'); 
//             // Apply filters
//             if ($request->has('id')) {
//                 $id = $request->input('id');
//                 $query->where('id', $id);
//                 \Log::info('Filter by ID: ' . $id);
//             }

//             // if ($request->has('search')) {
//             //     $query->where('name', 'like', '%' . $request->input('search') . '%');
//             // }
            
//             if ($request->has('search')) {
//             $searchTerm = $request->input('search');
//             $query->where(function($q) use ($searchTerm) {
//                 $q->where('name', 'like', '%' . $searchTerm . '%')
//                 ->orWhere('sku', 'like', '%' . $searchTerm . '%');
//             });
//             }

            
//             if ($request->has('name')) {
//                 $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
//             }


//             if ($request->has('description')) {
//                 $query->where('description', 'LIKE', '%' . $request->input('description') . '%');
//             }

//             if ($request->has('content')) {
//                 $query->where('content', 'LIKE', '%' . $request->input('content') . '%');
//             }

//             // SKU filter
//             if ($request->has('sku')) {
//                 $skus = $request->input('sku');
//                 if (is_array($skus)) {
//                     $query->whereIn('ec_products.sku', $skus); // Specify the table name
//                 } else {
//                     $query->where('ec_products.sku', $skus); // Specify the table name
//                 }
//             }
            

//             if ($request->has('status')) {
//                 $query->where('status', $request->input('status'));
//             }

//             if ($request->has('stock_status')) {
//                 $query->where('stock_status', $request->input('stock_status'));
//             }

//             if ($request->has('product_type')) {
//                 $query->where('product_type', $request->input('product_type'));
//             }

//             if ($request->has('store_id')) {
//                 $query->where('store_id', $request->input('store_id'));
//             }

//             // Numerical filters
//             if ($request->has('price_min')) {
//                 $query->where('price', '>=', $request->input('price_min'));
//             }

//             if ($request->has('price_max')) {
//                 $query->where('price', '<=', $request->input('price_max'));
//             }

//             if ($request->has('quantity_min')) {
//                 $query->where('quantity', '>=', $request->input('quantity_min'));
//             }

//             if ($request->has('quantity_max')) {
//                 $query->where('quantity', '<=', $request->input('quantity_max'));
//             }

//             // Date filters
//             if ($request->has('start_date')) {
//                 $query->where('created_at', '>=', $request->input('start_date'));
//             }

//             if ($request->has('end_date')) {
//                 $query->where('created_at', '<=', $request->input('end_date'));
//             }

//             // Boolean filters
//             if ($request->has('allow_checkout_when_out_of_stock')) {
//                 $query->where('allow_checkout_when_out_of_stock', $request->input('allow_checkout_when_out_of_stock'));
//             }

//             if ($request->has('with_storehouse_management')) {
//                 $query->where('with_storehouse_management', $request->input('with_storehouse_management'));
//             }

//             if ($request->has('is_featured')) {
//                 $query->where('is_featured', $request->input('is_featured'));
//             }

//             if ($request->has('is_variation')) {
//                 $query->where('is_variation', $request->input('is_variation'));
//             }

//             // Variation filters
//             if ($request->has('variant_grams')) {
//                 $query->where('variant_grams', $request->input('variant_grams'));
//             }

//             if ($request->has('variant_inventory_quantity')) {
//                 $query->where('variant_inventory_quantity', $request->input('variant_inventory_quantity'));
//             }

//             if ($request->has('variant_inventory_policy')) {
//                 $query->where('variant_inventory_policy', $request->input('variant_inventory_policy'));
//             }

//             if ($request->has('variant_fulfillment_service')) {
//                 $query->where('variant_fulfillment_service', $request->input('variant_fulfillment_service'));
//             }

//             if ($request->has('variant_requires_shipping')) {
//                 $query->where('variant_requires_shipping', $request->input('variant_requires_shipping'));
//             }

//             if ($request->has('variant_barcode')) {
//                 $query->where('variant_barcode', $request->input('variant_barcode'));
//             }

//             // Dimension filters
//             if ($request->has('length_min')) {
//                 $query->where('length', '>=', $request->input('length_min'));
//             }

//             if ($request->has('length_max')) {
//                 $query->where('length', '<=', $request->input('length_max'));
//             }

//             if ($request->has('width_min')) {
//                 $query->where('width', '>=', $request->input('width_min'));
//             }

//             if ($request->has('width_max')) {
//                 $query->where('width', '<=', $request->input('width_max'));
//             }

//             if ($request->has('height_min')) {
//                 $query->where('height', '>=', $request->input('height_min'));
//             }

//             if ($request->has('height_max')) {
//                 $query->where('height', '<=', $request->input('height_max'));
//             }

//             // Weight filters
//             if ($request->has('weight_min')) {
//                 $query->where('weight', '>=', $request->input('weight_min'));
//             }

//             if ($request->has('weight_max')) {
//                 $query->where('weight', '<=', $request->input('weight_max'));
//             }

//             // Rating filter
//             if ($request->has('rating')) {
//                 $rating = $request->input('rating');
//                 $query->whereHas('reviews', function($q) use ($rating) {
//                     $q->selectRaw('AVG(star) as avg_rating')
//                     ->groupBy('product_id')
//                     ->havingRaw('AVG(star) >= ?', [$rating]);
//                 });
//             }

//             // Brand filter
//             if ($request->has('brand_id')) {
//                 $query->where('brand_id', $request->input('brand_id'));
//             }
            
//         // Brand filter by name
//         if ($request->has('brand_names')) {
//             $brandNames = $request->input('brand_names');

//             // Check if $brandNames is an array
//             if (is_array($brandNames)) {
//                 // Fetch brand IDs based on names
//                 $brandIds = Brand::whereIn('name', $brandNames)->pluck('id');
                
//                 // Apply the filter using brand IDs
//                 $query->whereIn('brand_id', $brandIds);
//             } else {
//                 // If it's a single name, convert it into an array
//                 $brandIds = Brand::where('name', $brandNames)->pluck('id');
//                 $query->whereIn('brand_id', $brandIds);
//             }
//         }
//                 // Order by newest products first if no sorting is specified
//             if (!$request->has('sort_by')) {
//                 $query->orderBy('created_at', 'desc');
//             }

//             // Log the final SQL query for debugging
//             \Log::info($query->toSql());
//             \Log::info($query->getBindings());

        
//         // Get filtered product IDs
//         $filteredProductIds = $query->pluck('id');

//         // Subquery for best price and delivery date
//         $subQuery = Product::select('sku')
//             ->selectRaw('MIN(price) as best_price')
//             ->selectRaw('MIN(delivery_date) as best_delivery_date')
//             ->whereIn('id', $filteredProductIds)
//             ->groupBy('sku');

//         // Create the final products query while still respecting previous filters
//         $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
//             $join->on('ec_products.sku', '=', 'best_products.sku')
//                     ->whereColumn('ec_products.price', 'best_products.best_price');
//         })
//         ->whereIn('id', $filteredProductIds) // Add the filtered IDs back to ensure all filters are respected
//         ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
//         ->with('reviews', 'currency','specifications')
//         ->paginate($request->input('per_page', 15)); // Pagination

//             $brands = Brand::select('id', 'name')->get();


//         // Transform the result to include additional data
//         $products->getCollection()->transform(function ($product) {

//             // Add review and stock details (same as before)
//             $totalReviews = $product->reviews->count();
//             $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
//             $quantity = $product->quantity ?? 0;
//             $unitsSold = $product->units_sold ?? 0;
//             $leftStock = $quantity - $unitsSold;

//             $product->total_reviews = $totalReviews;
//             $product->avg_rating = $avgRating;
//             $product->leftStock = $leftStock;

//             // Add currency details
//             if ($product->currency) {
//                 $product->currency_title = $product->currency->is_prefix_symbol
//                     ? $product->currency->title
//                     : $product->price . ' ' . $product->currency->title;
//             } else {
//                 $product->currency_title = $product->price; // Fallback if no currency found
//             }
            
//             // Add specifications (same as before)
//             if ($product->specs_sheet) {
//                 $specifications = json_decode($product->specs_sheet, true);
//                 $filteredSpecs = array_map(function($spec) {
//                     return [
//                         'spec_name' => $spec['spec_name'] ?? null,
//                         'spec_value' => $spec['spec_value'] ?? null,
//                     ];
//                 }, $specifications);
//                 $product->specifications = $filteredSpecs;
//             }

//             // Handle frequently bought together products
//             if ($product->frequently_bought_together) {
//                 $frequentlyBoughtData = json_decode($product->frequently_bought_together, true);
//                 $frequentlyBoughtSkus = array_column($frequentlyBoughtData, 'value');
                
//                 $frequentlyBoughtProducts = Product::whereIn('sku', $frequentlyBoughtSkus)
//                     ->with('reviews', 'currency') // Include reviews and currency in query
//                     ->get();

//                 // Enhance frequently bought products with reviews and currency
//                 $frequentlyBoughtProducts->transform(function ($fbProduct) {
//                     $totalReviews = $fbProduct->reviews->count();
//                     $avgRating = $totalReviews > 0 ? $fbProduct->reviews->avg('star') : null;

//                     $fbProduct->total_reviews = $totalReviews;
//                     $fbProduct->avg_rating = $avgRating;

//                     if ($fbProduct->currency) {
//                         $fbProduct->currency_title = $fbProduct->currency->is_prefix_symbol
//                             ? $fbProduct->currency->title
//                             : $fbProduct->price . ' ' . $fbProduct->currency->title;
//                     } else {
//                         $fbProduct->currency_title = $fbProduct->price;
//                     }

//                     return $fbProduct;
//                 });

//                 $product->frequently_bought_together = $frequentlyBoughtProducts;
//             }

//             // Handle compare products
//             //   if ($product->compare_products) {
//             //       $compareIds = json_decode($product->compare_products, true);
                
//             //       $compareProducts = Product::whereIn('id', $compareIds)
//             //           ->with('reviews', 'currency') // Include reviews and currency in query
//             //           ->get();

//             //       // Enhance compare products with reviews and currency
//             //       $compareProducts->transform(function ($compareProduct) {
//             //           $totalReviews = $compareProduct->reviews->count();
//             //           $avgRating = $totalReviews > 0 ? $compareProduct->reviews->avg('star') : null;

//             //           $compareProduct->total_reviews = $totalReviews;
//             //           $compareProduct->avg_rating = $avgRating;

//             //           if ($compareProduct->currency) {
//             //               $compareProduct->currency_title = $compareProduct->currency->is_prefix_symbol
//             //                   ? $compareProduct->currency->title
//             //                   : $compareProduct->price . ' ' . $compareProduct->currency->title;
//             //           } else {
//             //               $compareProduct->currency_title = $compareProduct->price;
//             //           }

//             //           return $compareProduct;
//             //       });

//             //       $product->compare_products = $compareProducts;
//             //   }
//         if ($product->compare_products) {
//             $compareIds = json_decode($product->compare_products, true);

//             $compareProducts = Product::whereIn('id', $compareIds)
//                 ->with('reviews', 'currency' ,'specifications') // Include reviews and currency in query
//                 ->get();

//             // Enhance compare products with reviews, currency, and specifications
//             $compareProducts->transform(function ($compareProduct) {
//                 $totalReviews = $compareProduct->reviews->count();
//                 $avgRating = $totalReviews > 0 ? $compareProduct->reviews->avg('star') : null;

//                 $compareProduct->total_reviews = $totalReviews;
//                 $compareProduct->avg_rating = $avgRating;

//                 // Add currency details
//                 if ($compareProduct->currency) {
//                     $compareProduct->currency_title = $compareProduct->currency->is_prefix_symbol
//                         ? $compareProduct->currency->title
//                         : $compareProduct->price . ' ' . $compareProduct->currency->title;
//                 } else {
//                     $compareProduct->currency_title = $compareProduct->price;
//                 }

            
//                 // Add specifications similar to the main product
//                 if ($compareProduct->specs_sheet) {
//                     $specifications = json_decode($compareProduct->specs_sheet, true);
//                     $filteredSpecs = array_map(function($spec) {
//                         return [
//                             'spec_name' => $spec['spec_name'] ?? null,
//                             'spec_value' => $spec['spec_value'] ?? null,
//                         ];
//                     }, $specifications);
//                     $compareProduct->specifications = $filteredSpecs;
//                 }

//                 return $compareProduct;
//             });

//             $product->compare_products = $compareProducts;
//         }
//             return $product;
//         });

//         return response()->json([
//             'success' => true,
//             'data' => $products,
//             'brands' => $brands
//         ]);
// }



            // public function getAllProducts(Request $request)
            // {
            //     // Log the request to ensure you're receiving the correct parameters
            //     \Log::info($request->all());

            //     // Start building the query
            //     $query = Product::with('categories', 'brand', 'tags', 'producttypes'); // Added tags and producttypes

            //     // Apply filters
            //     $this->applyFilters($query, $request);

            //     // Order by newest products first if no sorting is specified
            //     if (!$request->has('sort_by')) {
            //         $query->orderBy('created_at', 'desc');
            //     }

            //     // Log the final SQL query for debugging
            //     \Log::info($query->toSql());
            //     \Log::info($query->getBindings());

            //     // Get filtered product IDs
            //     $filteredProductIds = $query->pluck('id');

            //     // Subquery for best price and delivery date
            //     $subQuery = Product::select('sku')
            //         ->selectRaw('MIN(price) as best_price')
            //         ->selectRaw('MIN(delivery_date) as best_delivery_date')
            //         ->whereIn('id', $filteredProductIds)
            //         ->groupBy('sku');

            //     // Create the final products query while still respecting previous filters
            //     $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
            //         $join->on('ec_products.sku', '=', 'best_products.sku')
            //             ->whereColumn('ec_products.price', 'best_products.best_price');
            //     })
            //     ->whereIn('id', $filteredProductIds) // Add the filtered IDs back to ensure all filters are respected
            //     ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
            //     ->with('reviews', 'currency', 'specifications')
            //     ->paginate($request->input('per_page', 15)); // Pagination

            //     $brands = Brand::select('id', 'name')->get();

            //     // Collect categories for all products
            //     $categories = $products->map(function ($product) {
            //         return $product->categories; // Assuming categories is a relationship in the Product model
            //     });

            //     // Transform the result to include additional data
            //     $products->getCollection()->transform(function ($product) {
            //         // Add review and stock details
            //         $totalReviews = $product->reviews->count();
            //         $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
            //         $quantity = $product->quantity ?? 0;
            //         $unitsSold = $product->units_sold ?? 0;
            //         $leftStock = $quantity - $unitsSold;

            //         $product->total_reviews = $totalReviews;
            //         $product->avg_rating = $avgRating;
            //         $product->leftStock = $leftStock;

            //         // Add currency details
            //         if ($product->currency) {
            //             $product->currency_title = $product->currency->is_prefix_symbol
            //                 ? $product->currency->title
            //                 : $product->price . ' ' . $product->currency->title;
            //         } else {
            //             $product->currency_title = $product->price; // Fallback if no currency found
            //         }

            //         // Add specifications
            //         if ($product->specs_sheet) {
            //             $specifications = json_decode($product->specs_sheet, true);
            //             $filteredSpecs = array_map(function($spec) {
            //                 return [
            //                     'spec_name' => $spec['spec_name'] ?? null,
            //                     'spec_value' => $spec['spec_value'] ?? null,
            //                 ];
            //             }, $specifications);
            //             $product->specifications = $filteredSpecs;
            //         }

            //         // Handle frequently bought together products
            //         if ($product->frequently_bought_together) {
            //             $frequentlyBoughtData = json_decode($product->frequently_bought_together, true);
            //             $frequentlyBoughtSkus = array_column($frequentlyBoughtData, 'value');

            //             $frequentlyBoughtProducts = Product::whereIn('sku', $frequentlyBoughtSkus)
            //                 ->with('reviews', 'currency') // Include reviews and currency in query
            //                 ->get();

            //             // Enhance frequently bought products with reviews and currency
            //             $frequentlyBoughtProducts->transform(function ($fbProduct) {
            //                 $totalReviews = $fbProduct->reviews->count();
            //                 $avgRating = $totalReviews > 0 ? $fbProduct->reviews->avg('star') : null;

            //                 $fbProduct->total_reviews = $totalReviews;
            //                 $fbProduct->avg_rating = $avgRating;

            //                 if ($fbProduct->currency) {
            //                     $fbProduct->currency_title = $fbProduct->currency->is_prefix_symbol
            //                         ? $fbProduct->currency->title
            //                         : $fbProduct->price . ' ' . $fbProduct->currency->title;
            //                 } else {
            //                     $fbProduct->currency_title = $fbProduct->price;
            //                 }

            //                 return $fbProduct;
            //             });

            //             $product->frequently_bought_together = $frequentlyBoughtProducts;
            //         }

            //         // Handle compare products
            //         if ($product->compare_products) {
            //             $compareIds = json_decode($product->compare_products, true);

            //             $compareProducts = Product::whereIn('id', $compareIds)
            //                 ->with('reviews', 'currency', 'specifications') // Include reviews and currency in query
            //                 ->get();

            //             // Enhance compare products with reviews, currency, and specifications
            //             $compareProducts->transform(function ($compareProduct) {
            //                 $totalReviews = $compareProduct->reviews->count();
            //                 $avgRating = $totalReviews > 0 ? $compareProduct->reviews->avg('star') : null;

            //                 $compareProduct->total_reviews = $totalReviews;
            //                 $compareProduct->avg_rating = $avgRating;

            //                 // Add currency details
            //                 if ($compareProduct->currency) {
            //                     $compareProduct->currency_title = $compareProduct->currency->is_prefix_symbol
            //                         ? $compareProduct->currency->title
            //                         : $compareProduct->price . ' ' . $compareProduct->currency->title;
            //                 } else {
            //                     $compareProduct->currency_title = $compareProduct->price;
            //                 }

            //                 // Add specifications
            //                 if ($compareProduct->specs_sheet) {
            //                     $specifications = json_decode($compareProduct->specs_sheet, true);
            //                     $filteredSpecs = array_map(function($spec) {
            //                         return [
            //                             'spec_name' => $spec['spec_name'] ?? null,
            //                             'spec_value' => $spec['spec_value'] ?? null,
            //                         ];
            //                     }, $specifications);
            //                     $compareProduct->specifications = $filteredSpecs;
            //                 }

            //                 return $compareProduct;
            //             });

            //             $product->compare_products = $compareProducts;
            //         }

            //         // Add tags and types
            //         $product->tags = $product->tags; // Assuming tags is a relationship in the Product model
            //         $product->producttypes = $product->producttypes; // Assuming producttypes is a relationship in the Product model

            //         return $product;
            //     });

            //     return response()->json([
            //         'success' => true,
            //         'data' => $products,
            //         'brands' => $brands,
            //         'categories' => $categories // This will hold categories for all products
            //     ]);
            // }
       
























    // public function getAllProducts(Request $request)
    // {
    //     // Fetching products with possible filters
    //     $query = Product::query();

       

    //     if ($request->has('search')) {
    //         $query->where('name', 'like', '%' . $request->input('search') . '%');
    //     }
    //      // Text-based filters
    //      if ($request->has('name')) {
    //         $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
    //     }
    //     if ($request->has('description')) {
    //         $query->where('description', 'LIKE', '%' . $request->input('description') . '%');
    //     }
    //     if ($request->has('content')) {
    //         $query->where('content', 'LIKE', '%' . $request->input('content') . '%');
    //     }
    //     if ($request->has('sku')) {
    //         $query->where('sku', $request->input('sku'));
    //     }

    //     // Status filters
    //     if ($request->has('status')) {
    //         $query->whereHas('status', function($q) use ($request) {
    //             $q->where('value', $request->input('status'));
    //         });
    //     }
          
    //     if ($request->has('stock_status')) {
    //         $stockStatusValue = $request->input('stock_status');
    //         $query->where('stock_status', $stockStatusValue);
    //     }

       
    //     if ($request->has('product_type')) {
    //         $query->whereHas('product_type', function($q) use ($request) {
    //             $q->where('value', $request->input('product_type'));
    //         });
    //     }
    //             // Add filter for store_id
    //             if ($request->has('store_id')) {
    //                 $query->where('store_id', $request->input('store_id'));
    //             }
    //     // Numerical filters
    //     if ($request->has('price_min')) {
    //         $query->where('price', '>=', $request->input('price_min'));
    //     }
    //     if ($request->has('price_max')) {
    //         $query->where('price', '<=', $request->input('price_max'));
    //     }
      
    //     if ($request->has('quantity_min')) {
    //         $query->where('quantity', '>=', $request->input('quantity_min'));
    //     }
    //     if ($request->has('quantity_max')) {
    //         $query->where('quantity', '<=', $request->input('quantity_max'));
    //     }

    //     // Date filters
    //     if ($request->has('start_date')) {
    //         $query->where('created_at', '>=', $request->input('start_date'));
    //     }
    //     if ($request->has('end_date')) {
    //         $query->where('created_at', '<=', $request->input('end_date'));
    //     }

    //     // Boolean filters
    //     if ($request->has('allow_checkout_when_out_of_stock')) {
    //         $query->where('allow_checkout_when_out_of_stock', $request->input('allow_checkout_when_out_of_stock'));
    //     }
    //     if ($request->has('with_storehouse_management')) {
    //         $query->where('with_storehouse_management', $request->input('with_storehouse_management'));
    //     }
    //     if ($request->has('is_featured')) {
    //         $query->where('is_featured', $request->input('is_featured'));
    //     }
    //     if ($request->has('is_variation')) {
    //         $query->where('is_variation', $request->input('is_variation'));
    //     }

    //     // Variation filters
    //     if ($request->has('variant_grams')) {
    //         $query->where('variant_grams', $request->input('variant_grams'));
    //     }
    //     if ($request->has('variant_inventory_quantity')) {
    //         $query->where('variant_inventory_quantity', $request->input('variant_inventory_quantity'));
    //     }
    //     if ($request->has('variant_inventory_policy')) {
    //         $query->where('variant_inventory_policy', $request->input('variant_inventory_policy'));
    //     }
    //     if ($request->has('variant_fulfillment_service')) {
    //         $query->where('variant_fulfillment_service', $request->input('variant_fulfillment_service'));
    //     }
    //     if ($request->has('variant_requires_shipping')) {
    //         $query->where('variant_requires_shipping', $request->input('variant_requires_shipping'));
    //     }
    //     if ($request->has('variant_barcode')) {
    //         $query->where('variant_barcode', $request->input('variant_barcode'));
    //     }

    //     // Dimension filters
    //     if ($request->has('length_min')) {
    //         $query->where('length', '>=', $request->input('length_min'));
    //     }
    //     if ($request->has('length_max')) {
    //         $query->where('length', '<=', $request->input('length_max'));
    //     }
    //     if ($request->has('width_min')) {
    //         $query->where('width', '>=', $request->input('width_min'));
    //     }
    //     if ($request->has('width_max')) {
    //         $query->where('width', '<=', $request->input('width_max'));
    //     }
    //     if ($request->has('height_min')) {
    //         $query->where('height', '>=', $request->input('height_min'));
    //     }
    //     if ($request->has('height_max')) {
    //         $query->where('height', '<=', $request->input('height_max'));
    //     }

    //     // Weight filter
    //     if ($request->has('weight_min')) {
    //         $query->where('weight', '>=', $request->input('weight_min'));
    //     }
    //     if ($request->has('weight_max')) {
    //         $query->where('weight', '<=', $request->input('weight_max'));
    //     }
    //     if ($request->has('rating')) {
    //         $rating = $request->input('rating');
    //         $query->whereHas('reviews', function($q) use ($rating) {
    //             $q->selectRaw('AVG(star) as avg_rating')
    //               ->groupBy('product_id')
    //               ->havingRaw('AVG(star) >= ?', [$rating]);
    //         });
    //     }


    //     if ($request->has('brand_id')) {
    //         $brandId = $request->input('brand_id');
    //         $query->where('brand_id', $brandId);
    //     }
    //     // Pagination
    //     $perPage = $request->input('per_page', 15);
    //     $products = $query->paginate($perPage);

    //     return response()->json([
    //         'success' => true,
    //         'data' => $products
    //     ]);
    // }

//     public function getAllProducts(Request $request)
// {
//     // Fetching products with possible filters
//     $query = Product::query();

//     if ($request->has('search')) {
//         $query->where('name', 'like', '%' . $request->input('search') . '%');
//     }
    
//     // Text-based filters
//     if ($request->has('name')) {
//         $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
//     }
//     if ($request->has('description')) {
//         $query->where('description', 'LIKE', '%' . $request->input('description') . '%');
//     }
//     if ($request->has('content')) {
//         $query->where('content', 'LIKE', '%' . $request->input('content') . '%');
//     }
//     if ($request->has('sku')) {
//         $query->where('sku', $request->input('sku'));
//     }

//     // Status filters
//     if ($request->has('status')) {
//         $query->whereHas('status', function($q) use ($request) {
//             $q->where('value', $request->input('status'));
//         });
//     }
    
//     if ($request->has('stock_status')) {
//         $stockStatusValue = $request->input('stock_status');
//         $query->where('stock_status', $stockStatusValue);
//     }

//     if ($request->has('product_type')) {
//         $query->whereHas('product_type', function($q) use ($request) {
//             $q->where('value', $request->input('product_type'));
//         });
//     }

//     // Filter by store_id
//     if ($request->has('store_id')) {
//         $query->where('store_id', $request->input('store_id'));
//     }

//     // Numerical filters
//     if ($request->has('price_min')) {
//         $query->where('price', '>=', $request->input('price_min'));
//     }
//     if ($request->has('price_max')) {
//         $query->where('price', '<=', $request->input('price_max'));
//     }
//     if ($request->has('quantity_min')) {
//         $query->where('quantity', '>=', $request->input('quantity_min'));
//     }
//     if ($request->has('quantity_max')) {
//         $query->where('quantity', '<=', $request->input('quantity_max'));
//     }

//     // Date filters
//     if ($request->has('start_date')) {
//         $query->where('created_at', '>=', $request->input('start_date'));
//     }
//     if ($request->has('end_date')) {
//         $query->where('created_at', '<=', $request->input('end_date'));
//     }

//     // Boolean filters
//     if ($request->has('allow_checkout_when_out_of_stock')) {
//         $query->where('allow_checkout_when_out_of_stock', $request->input('allow_checkout_when_out_of_stock'));
//     }
//     if ($request->has('with_storehouse_management')) {
//         $query->where('with_storehouse_management', $request->input('with_storehouse_management'));
//     }
//     if ($request->has('is_featured')) {
//         $query->where('is_featured', $request->input('is_featured'));
//     }
//     if ($request->has('is_variation')) {
//         $query->where('is_variation', $request->input('is_variation'));
//     }

//     // Variation filters
//     if ($request->has('variant_grams')) {
//         $query->where('variant_grams', $request->input('variant_grams'));
//     }
//     if ($request->has('variant_inventory_quantity')) {
//         $query->where('variant_inventory_quantity', $request->input('variant_inventory_quantity'));
//     }
//     if ($request->has('variant_inventory_policy')) {
//         $query->where('variant_inventory_policy', $request->input('variant_inventory_policy'));
//     }
//     if ($request->has('variant_fulfillment_service')) {
//         $query->where('variant_fulfillment_service', $request->input('variant_fulfillment_service'));
//     }
//     if ($request->has('variant_requires_shipping')) {
//         $query->where('variant_requires_shipping', $request->input('variant_requires_shipping'));
//     }
//     if ($request->has('variant_barcode')) {
//         $query->where('variant_barcode', $request->input('variant_barcode'));
//     }

//     // Dimension filters
//     if ($request->has('length_min')) {
//         $query->where('length', '>=', $request->input('length_min'));
//     }
//     if ($request->has('length_max')) {
//         $query->where('length', '<=', $request->input('length_max'));
//     }
//     if ($request->has('width_min')) {
//         $query->where('width', '>=', $request->input('width_min'));
//     }
//     if ($request->has('width_max')) {
//         $query->where('width', '<=', $request->input('width_max'));
//     }
//     if ($request->has('height_min')) {
//         $query->where('height', '>=', $request->input('height_min'));
//     }
//     if ($request->has('height_max')) {
//         $query->where('height', '<=', $request->input('height_max'));
//     }

//     // Weight filter
//     if ($request->has('weight_min')) {
//         $query->where('weight', '>=', $request->input('weight_min'));
//     }
//     if ($request->has('weight_max')) {
//         $query->where('weight', '<=', $request->input('weight_max'));
//     }
//     if ($request->has('rating')) {
//         $rating = $request->input('rating');
//         $query->whereHas('reviews', function($q) use ($rating) {
//             $q->selectRaw('AVG(star) as avg_rating')
//               ->groupBy('product_id')
//               ->havingRaw('AVG(star) >= ?', [$rating]);
//         });
//     }

//     if ($request->has('brand_id')) {
//         $brandId = $request->input('brand_id');
//         $query->where('brand_id', $brandId);
//     }

//     // Select the specified columns
//     $query->select([
//         'id',
//         'name',
//         'description',
//         'content',
//         'status',
//         'images',
//         'sku',
//         'order',
//         'quantity',
//         'allow_checkout_when_out_of_stock',
//         'with_storehouse_management',
//         'is_featured',
//         'brand_id',
//         'is_variation',
//         'sale_type',
//         'price',
//         'sale_price',
//         'start_date',
//         'end_date',
//         'length',
//         'length_unit_id',
//         'width',
//         'width_unit_id',
//         'height',
//         'height_unit_id',
//         'depth',
//         'depth_unit_id',
//         'weight',
//         'weight_unit_id',
//         'tax_id',
//         'views',
//         'created_at',
//         'updated_at',
//         'stock_status',
//         'created_by_id',
//         'created_by_type',
//         'image',
//         'product_type',
//         'barcode',
//         'cost_per_item',
//         'minimum_order_quantity',
//         'maximum_order_quantity',
//         'store_id',
//         'approved_by',
//         'warranty_information',
//         'handle',
//         'variant_grams',
//         'variant_inventory_tracker',
//         'variant_inventory_quantity',
//         'variant_inventory_policy',
//         'variant_fulfillment_service',
//         'variant_requires_shipping',
//         'unit_of_weight_id',
//         'unit_of_measurement_id',
//         'variant_barcode',
//         'gift_card',
//         'seo_title',
//         'seo_description',
//         'google_shopping_category',
//         'google_shopping_mpn',
//         'box_quantity',
//         'documents',
//         'video_url',
//         'video_path',
//         'units_sold',
//         'refund_policy',
//         'shipping_weight_option',
//         'shipping_weight',
//         'shipping_dimension_option',
//         'shipping_width',
//         'shipping_width_id',
//         'shipping_depth',
//         'shipping_depth_id',
//         'shipping_height',
//         'shipping_height_id',
//         'shipping_length',
//         'shipping_length_id',
//         'frequently_bought_together',
//         'compare_type',
//         'compare_products',
//         'refund',
//     ]);

//     // Pagination
//     $perPage = $request->input('per_page', 15);
//     $products = $query->paginate($perPage);

//     return response()->json([
//         'success' => true,
//         'data' => $products
//     ]);
// }


// public function getAllProducts(Request $request)
// {
//     // Fetching products with possible filters
//     $query = \DB::table('ec_products'); // Using DB facade to directly reference the table

//     // Apply existing filters
//     if ($request->has('search')) {
//         $query->where('name', 'like', '%' . $request->input('search') . '%');
//     }

//     // Text-based filters
//     if ($request->has('name')) {
//         $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
//     }
//     if ($request->has('description')) {
//         $query->where('description', 'LIKE', '%' . $request->input('description') . '%');
//     }
//     if ($request->has('content')) {
//         $query->where('content', 'LIKE', '%' . $request->input('content') . '%');
//     }
//     if ($request->has('sku')) {
//         $query->where('sku', $request->input('sku'));
//     }

//     // Status filters
//     if ($request->has('status')) {
//         $query->where('status', $request->input('status'));
//     }

//     if ($request->has('stock_status')) {
//         $stockStatusValue = $request->input('stock_status');
//         $query->where('stock_status', $stockStatusValue);
//     }

//     if ($request->has('product_type')) {
//         $query->where('product_type', $request->input('product_type'));
//     }

//     // Filter by store_id
//     if ($request->has('store_id')) {
//         $query->where('store_id', $request->input('store_id'));
//     }

//     // Numerical filters
//     if ($request->has('price_min')) {
//         $query->where('price', '>=', $request->input('price_min'));
//     }
//     if ($request->has('price_max')) {
//         $query->where('price', '<=', $request->input('price_max'));
//     }
//     if ($request->has('quantity_min')) {
//         $query->where('quantity', '>=', $request->input('quantity_min'));
//     }
//     if ($request->has('quantity_max')) {
//         $query->where('quantity', '<=', $request->input('quantity_max'));
//     }

//     // Date filters
//     if ($request->has('start_date')) {
//         $query->where('created_at', '>=', $request->input('start_date'));
//     }
//     if ($request->has('end_date')) {
//         $query->where('created_at', '<=', $request->input('end_date'));
//     }

//     // Boolean filters
//     if ($request->has('allow_checkout_when_out_of_stock')) {
//         $query->where('allow_checkout_when_out_of_stock', $request->input('allow_checkout_when_out_of_stock'));
//     }
//     if ($request->has('with_storehouse_management')) {
//         $query->where('with_storehouse_management', $request->input('with_storehouse_management'));
//     }
//     if ($request->has('is_featured')) {
//         $query->where('is_featured', $request->input('is_featured'));
//     }
//     if ($request->has('is_variation')) {
//         $query->where('is_variation', $request->input('is_variation'));
//     }

//     // Variation filters
//     if ($request->has('variant_grams')) {
//         $query->where('variant_grams', $request->input('variant_grams'));
//     }
//     if ($request->has('variant_inventory_quantity')) {
//         $query->where('variant_inventory_quantity', $request->input('variant_inventory_quantity'));
//     }
//     if ($request->has('variant_inventory_policy')) {
//         $query->where('variant_inventory_policy', $request->input('variant_inventory_policy'));
//     }
//     if ($request->has('variant_fulfillment_service')) {
//         $query->where('variant_fulfillment_service', $request->input('variant_fulfillment_service'));
//     }
//     if ($request->has('variant_requires_shipping')) {
//         $query->where('variant_requires_shipping', $request->input('variant_requires_shipping'));
//     }
//     if ($request->has('variant_barcode')) {
//         $query->where('variant_barcode', $request->input('variant_barcode'));
//     }

//     // Dimension filters
//     if ($request->has('length_min')) {
//         $query->where('length', '>=', $request->input('length_min'));
//     }
//     if ($request->has('length_max')) {
//         $query->where('length', '<=', $request->input('length_max'));
//     }
//     if ($request->has('width_min')) {
//         $query->where('width', '>=', $request->input('width_min'));
//     }
//     if ($request->has('width_max')) {
//         $query->where('width', '<=', $request->input('width_max'));
//     }
//     if ($request->has('height_min')) {
//         $query->where('height', '>=', $request->input('height_min'));
//     }
//     if ($request->has('height_max')) {
//         $query->where('height', '<=', $request->input('height_max'));
//     }

//     // Weight filter
//     if ($request->has('weight_min')) {
//         $query->where('weight', '>=', $request->input('weight_min'));
//     }
//     if ($request->has('weight_max')) {
//         $query->where('weight', '<=', $request->input('weight_max'));
//     }

//     // Group by SKU to get unique products
//     $subQuery = \DB::table('ec_products')
//         ->select('sku')
//         ->selectRaw('MIN(price) as best_price')
//         ->selectRaw('MIN(delivery_date) as best_delivery_date')
//         ->groupBy('sku');

//     // Join with the products table to get full details for best products
//     $products = \DB::table('ec_products')
//         ->joinSub($subQuery, 'best_products', function ($join) {
//             $join->on('ec_products.sku', '=', 'best_products.sku')
//                  ->whereColumn('ec_products.price', 'best_products.best_price');
//         })
//         ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
//         ->paginate($request->input('per_page', 15));

//     return response()->json([
//         'success' => true,
//         'data' => $products
//     ]);
// }











}
