<?php

namespace App\Http\Controllers\API;
use RvMedia;
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
                // Keep existing user and wishlist logic
                $userId = Auth::id();
                $isUserLoggedIn = $userId !== null;

                Log::info('User logged in:', ['user_id' => $userId]);

                $wishlistProductIds = [];
                if ($isUserLoggedIn) {
                    $wishlistProductIds = DB::table('ec_wish_lists')
                        ->where('customer_id', $userId)
                        ->pluck('product_id')
                        ->map(function($id) {
                            return (int) $id;
                        })
                        ->toArray();
                } else {
                    $wishlistProductIds = session()->get('guest_wishlist', []);
                }

                // Start building the base query
                $query = Product::with(['categories', 'brand' , 'brand.products.reviews'])
                    ->where('status', 'published');

                // Apply filters
                $this->applyFilters($query, $request);

                // Log query for debugging
                \Log::info($query->toSql());
                \Log::info($query->getBindings());

                // Get filtered IDs efficiently
                $filteredProductIds = $query->pluck('id');

                // Calculate min-max values only for filtered products
                $priceMin = Product::whereIn('id', $filteredProductIds)->min('sale_price');
                $priceMax = Product::whereIn('id', $filteredProductIds)->max('sale_price');

                $DeliveryMin = Product::whereIn('id', $filteredProductIds)
                    ->whereNotNull('delivery_days')
                    ->selectRaw('MIN(CAST(delivery_days AS UNSIGNED)) as min_delivery_days')
                    ->value('min_delivery_days');

                $DeliveryMax = Product::whereIn('id', $filteredProductIds)
                    ->whereNotNull('delivery_days')
                    ->selectRaw('MAX(CAST(delivery_days AS UNSIGNED)) as max_delivery_days')
                    ->value('max_delivery_days');

                // Get sort parameter
                $sortBy = $request->input('sort_by', 'created_at');
                $validSortOptions = ['created_at', 'price', 'name'];
                if (!in_array($sortBy, $validSortOptions)) {
                    $sortBy = 'created_at';
                }

                // Subquery for best price and delivery date
                $subQuery = Product::select('sku')
                    ->selectRaw('MIN(price) as best_price')
                    ->selectRaw('MIN(delivery_days) as best_delivery_date')
                    ->whereIn('id', $filteredProductIds)
                    ->groupBy('sku');

                // Paginate efficiently - only get the required number of products
                $perPage = 50;
                $page = $request->input('page', 1);

                $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                    $join->on('ec_products.sku', '=', 'best_products.sku')
                        ->whereColumn('ec_products.price', 'best_products.best_price');
                })
                ->whereIn('id', $filteredProductIds)
                ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
                ->with([
                    'reviews' => function($query) {
                        $query->select('id', 'product_id', 'star');
                    },
                    'currency',
                    'specifications'
                ])
                ->orderBy($sortBy, 'desc')
                ->paginate($perPage);

                // Add query parameters to pagination
                $products->appends($request->all());

                // Calculate pagination details
                $currentPage = $products->currentPage();
                $lastPage = $products->lastPage();
                $startPage = max($currentPage - 2, 1);
                $endPage = min($startPage + 4, $lastPage);

                if ($endPage - $startPage < 4) {
                    $startPage = max($endPage - 4, 1);
                }

                $pagination = [
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $products->total(),
                    'has_more_pages' => $products->hasMorePages(),
                    'visible_pages' => range($startPage, $endPage),
                    'has_previous' => $currentPage > 1,
                    'has_next' => $currentPage < $lastPage,
                    'previous_page' => $currentPage - 1,
                    'next_page' => $currentPage + 1,
                ];

                // Get categories and brands (consider caching these)
                $categories = ProductCategory::select('id', 'name')->get();
                $brands = Brand::select('id', 'name')->get();

                    // Transform the products collection
                    $products->getCollection()->transform(function ($product) use ($wishlistProductIds) {

                        $product->benefits_features = json_decode($product->benefits_features, true);


                        if (is_string($product->description)) {
                            $product->description = json_decode($product->description, true);
                        }

                        if ($product->brand) {
                            $product->brand_id = $product->brand->id;
                            $product->brand_name = $product->brand->name;
                            $product->brand_logo = $product->brand->logo;
                        
                            // Get review stats directly from the database
                            $brandProductIds = \DB::table('ec_products')
                                ->where('brand_id', $product->brand->id)
                                ->pluck('id');
                        
                            $brandReviewsQuery = \DB::table('ec_reviews')
                                ->whereIn('product_id', $brandProductIds);
                        
                            $brandReviewCount = $brandReviewsQuery->count();
                            $brandAvgRating = $brandReviewCount > 0
                                ? round($brandReviewsQuery->avg('star'), 1)
                                : null;
                        
                            $product->brand_avg_rating = $brandAvgRating;
                            $product->brand_review_count = $brandReviewCount;
                        }
                        
                        $product->images = collect($product->images)->map(function ($image) {
                            return $image; // Already a full URL, just return it
                        });

                        // Handle videos
                        $videoPaths = json_decode($product->video_path, true);
                        $product->video_path = collect($videoPaths)->map(function ($video) {
                            return $video; // Already a full URL, just return it
                        });

                        if ($product->sellingUnitAttribute && $product->sellingUnitAttribute->attribute_value) {
                            $fullValue = $product->sellingUnitAttribute->attribute_value;
                            if (strpos($fullValue, '/') !== false) {
                                $parts = explode('/', $fullValue);
                                $product->sellingUnitAttribute->attribute_value_unit = trim($parts[1]);
                            } else {
                                $product->sellingUnitAttribute->attribute_value_unit = $fullValue;
                            }
                        }
                        
                        // Add review and stock details
                        $totalReviews = $product->reviews->count();
                        $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
                        $quantity = $product->quantity ?? 0;
                        $unitsSold = $product->units_sold ?? 0;
                        $leftStock = $quantity - $unitsSold;

                        $product->total_reviews = $totalReviews;
                        $product->avg_rating = $avgRating;
                        $product->leftStock = $leftStock;
                        $product->in_wishlist = in_array($product->id, $wishlistProductIds);

                        // Handle currency
                        if ($product->currency) {
                            $product->currency_title = $product->currency->is_prefix_symbol
                                ? $product->currency->title
                                : $product->price . ' ' . $product->currency->title;
                        } else {
                            $product->currency_title = $product->price;
                        }

                        // Handle same SKU products
                        $sameSkuProducts = Product::where('sku', $product->sku)
                            ->where('id', '!=', $product->id)
                            ->select('id', 'name', 'price', 'delivery_days', 'images', 'currency_id')
                            ->with('currency')
                            ->get();

                        $product->same_sku_product_ids = $sameSkuProducts->map(function ($item) {
                            $currencyTitle = $item->currency
                                ? ($item->currency->is_prefix_symbol
                                    ? $item->currency->title
                                    : $item->price . ' ' . $item->currency->title)
                                : $item->price;

                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'price' => $item->price,
                                'delivery_days' => $item->delivery_days,
                                'images' => $item->images,
                                'currency_title' => $currencyTitle,
                            ];
                        });

                        // Handle same brand SKU products
                        $sameBrandSkuProducts = Product::where('sku', $product->sku)
                            ->where('id', '!=', $product->id)
                            ->where('brand_id', $product->brand_id)
                            ->select('id', 'name', 'images')
                            ->get();

                        $product->sameBrandSkuProducts = $sameBrandSkuProducts->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'images' => $item->images
                            ];
                        });
                        $product->category_list = $product->categories->map(function ($category) {
                            return [
                                'id' => $category->id,
                                'name' => $category->name,
                                'slug' => optional($category->slugable)->key, // Get slug from the slugs table
                            ];
                        });

                        return $product;
                    });

                    return response()->json([
                        'success' => true,
                        'data' => $products,
                        'pagination' => $pagination,
                        'brands' => $brands,
                        'categories' => $categories,
                        'price_min' => $priceMin,
                        'price_max' => $priceMax
            
                    ]);
    }

   

    

    public function getAllPublicProducts(Request $request)
    {

              // Start building the base query
                $query = Product::with(['categories', 'brand', 'brand.products.reviews'])
                    ->where('status', 'published');

                $this->applyFilters($query, $request);

                // Log query for debugging
                \Log::info($query->toSql());
                \Log::info($query->getBindings());

                // Get filtered IDs efficiently
                $filteredProductIds = $query->pluck('id');

                // Calculate min-max values only for filtered products
                $priceMin = Product::whereIn('id', $filteredProductIds)->min('sale_price');
                $priceMax = Product::whereIn('id', $filteredProductIds)->max('sale_price');
              

                $DeliveryMin = Product::whereIn('id', $filteredProductIds)
                    ->whereNotNull('delivery_days')
                    ->selectRaw('MIN(CAST(delivery_days AS UNSIGNED)) as min_delivery_days')
                    ->value('min_delivery_days');

                $DeliveryMax = Product::whereIn('id', $filteredProductIds)
                    ->whereNotNull('delivery_days')
                    ->selectRaw('MAX(CAST(delivery_days AS UNSIGNED)) as max_delivery_days')
                    ->value('max_delivery_days');

                // Get sort parameter
                $validSortOptions = ['created_at', 'price', 'name'];
                $sortBy = $request->input('sort_by', 'created_at');

                // if (!in_array($sortBy, $validSortOptions)) {
                //     $sortBy = 'created_at';
                // }

                // Subquery for best price and delivery date
                $subQuery = Product::select('sku')
                    ->selectRaw('MIN(price) as best_price')
                    ->selectRaw('MIN(delivery_days) as best_delivery_date')
                    ->whereIn('id', $filteredProductIds)
                    ->groupBy('sku');

                // Paginate efficiently - only get the required number of products
                $perPage = 30;
                $page = $request->input('page', 1);

                $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                    $join->on('ec_products.sku', '=', 'best_products.sku')
                        ->whereColumn('ec_products.price', 'best_products.best_price');
                })
                ->whereIn('id', $filteredProductIds)
                ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
                ->with([
                    'reviews' => function($query) {
                        $query->select('id', 'product_id', 'star');
                    },
                    'currency'
                ])
                ->orderBy($sortBy, 'desc')
                ->paginate($perPage);

                // Add query parameters to pagination
                $products->appends($request->all());

                // Calculate pagination details
                $currentPage = $products->currentPage();
                $lastPage = $products->lastPage();
                $startPage = max($currentPage - 2, 1);
                $endPage = min($startPage + 4, $lastPage);

                if ($endPage - $startPage < 4) {
                    $startPage = max($endPage - 4, 1);
                }

                $pagination = [
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $products->total(),
                    'has_more_pages' => $products->hasMorePages(),
                    'visible_pages' => range($startPage, $endPage),
                    'has_previous' => $currentPage > 1,
                    'has_next' => $currentPage < $lastPage,
                    'previous_page' => $currentPage - 1,
                    'next_page' => $currentPage + 1,
                ];

                // Get categories and brands (consider caching these)
                // $categories = ProductCategory::select('id', 'name')->get();
                $brands = Brand::select('id', 'name')->get();

                    // Transform the products collection
                    $products->getCollection()->transform(function ($product) {

                        $product->benefits_features = json_decode($product->benefits_features, true);
                    
                        if (is_string($product->description)) {
                            $product->description = json_decode($product->description, true);
                        }
                    
                        if ($product->brand) {
                            $product->brand_id = $product->brand->id;
                            $product->brand_name = $product->brand->name;
                            $product->brand_logo = $product->brand->logo;
                        
                            // Get review stats directly from the database
                            $brandProductIds = \DB::table('ec_products')
                                ->where('brand_id', $product->brand->id)
                                ->pluck('id');
                        
                            $brandReviewsQuery = \DB::table('ec_reviews')
                                ->whereIn('product_id', $brandProductIds);
                        
                            $brandReviewCount = $brandReviewsQuery->count();
                            $brandAvgRating = $brandReviewCount > 0
                                ? round($brandReviewsQuery->avg('star'), 1)
                                : null;
                        
                            $product->brand_avg_rating = $brandAvgRating;
                            $product->brand_review_count = $brandReviewCount;
                        }
                        
                        $product->images = collect($product->images)->map(function ($image) {
                            return $image;
                        });
                    
                        $videoPaths = json_decode($product->video_path, true);
                        $product->video_path = collect($videoPaths)->map(function ($video) {
                            return $video;
                        });
                    
                        if ($product->sellingUnitAttribute && $product->sellingUnitAttribute->attribute_value) {
                            $fullValue = $product->sellingUnitAttribute->attribute_value;
                            if (strpos($fullValue, '/') !== false) {
                                $parts = explode('/', $fullValue);
                                $product->sellingUnitAttribute->attribute_value_unit = trim($parts[1]);
                            } else {
                                $product->sellingUnitAttribute->attribute_value_unit = $fullValue;
                            }
                        }
                    
                        // Reviews and stock
                        $totalReviews = $product->reviews->count();
                        $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
                        $quantity = $product->quantity ?? 0;
                        $unitsSold = $product->units_sold ?? 0;
                        $leftStock = $quantity - $unitsSold;
                    
                        $product->total_reviews = $totalReviews;
                        $product->avg_rating = $avgRating;
                        $product->leftStock = $leftStock;
                    
                        // Currency
                        if ($product->currency) {
                            $product->currency_title = $product->currency->is_prefix_symbol
                                ? $product->currency->title
                                : $product->price . ' ' . $product->currency->title;
                        } else {
                            $product->currency_title = $product->price;
                        }
                    
                        // ❌ Removed specifications section
                    
                        // ❌ Removed frequently bought together section
                    
                        return $product;
                    });
                    

                    return response()->json([
                        'success' => true,
                        'data' => $products,
                        'pagination' => $pagination,
                        'brands' => $brands,
                        // 'categories' => $categories,
                        'price_min' => $priceMin,
                        'price_max' => $priceMax
        
                    ]);
    }

    public function getAllProductsLising(Request $request)
    {
        // Keep existing user and wishlist logic
        $userId = Auth::id();
        $isUserLoggedIn = $userId !== null;

        Log::info('User logged in:', ['user_id' => $userId]);

        $wishlistProductIds = [];
        if ($isUserLoggedIn) {
            $wishlistProductIds = DB::table('ec_wish_lists')
                ->where('customer_id', $userId)
                ->pluck('product_id')
                ->map(function($id) {
                    return (int) $id;
                })
                ->toArray();
        } else {
            $wishlistProductIds = session()->get('guest_wishlist', []);
        }

        // Start building the base query
        $query = Product::with(['categories', 'brand' , 'brand.products.reviews'])
            ->where('status', 'published');

        // Apply filters
        $this->applyFilters($query, $request);

        // Log query for debugging
        \Log::info($query->toSql());
        \Log::info($query->getBindings());

        // Get filtered IDs efficiently
        $filteredProductIds = $query->pluck('id');

        // Calculate min-max values only for filtered products
        $priceMin = Product::whereIn('id', $filteredProductIds)->min('sale_price');
        $priceMax = Product::whereIn('id', $filteredProductIds)->max('sale_price');
      
        $DeliveryMin = Product::whereIn('id', $filteredProductIds)
            ->whereNotNull('delivery_days')
            ->selectRaw('MIN(CAST(delivery_days AS UNSIGNED)) as min_delivery_days')
            ->value('min_delivery_days');

        $DeliveryMax = Product::whereIn('id', $filteredProductIds)
            ->whereNotNull('delivery_days')
            ->selectRaw('MAX(CAST(delivery_days AS UNSIGNED)) as max_delivery_days')
            ->value('max_delivery_days');

        // Get sort parameter
        $sortBy = $request->input('sort_by', 'created_at');
        $validSortOptions = ['created_at', 'price', 'name'];
        if (!in_array($sortBy, $validSortOptions)) {
            $sortBy = 'created_at';
        }
        $sortByType = $request->input('sort_by_type', 'desc');
        if (!in_array($sortByType, ['asc', 'desc'])) {
            $sortByType = 'desc';
        }

        // Subquery for best price and delivery date
        $subQuery = Product::select('sku')
            ->selectRaw('MIN(price) as best_price')
            ->selectRaw('MIN(delivery_days) as best_delivery_date')
            ->whereIn('id', $filteredProductIds)
            ->groupBy('sku');

        // Paginate efficiently - only get the required number of products
        $perPage = 50;
        $page = $request->input('page', 1);

        $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
            $join->on('ec_products.sku', '=', 'best_products.sku')
                ->whereColumn('ec_products.price', 'best_products.best_price');
        })
        ->whereIn('id', $filteredProductIds)
        ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
        ->with([
            'reviews' => function($query) {
                $query->select('id', 'product_id', 'star');
            },
            'currency',
            'specifications'
        ]);
        if ($sortBy == 'price') {
            $products = $products->orderByRaw("COALESCE(sale_price, price) $sortByType");
        } else {
            $products = $products->orderBy($sortBy, $sortByType);
        }

        $products = $products->paginate($perPage);

        // Add query parameters to pagination
        $products->appends($request->all());

        // Calculate pagination details
        $currentPage = $products->currentPage();
        $lastPage = $products->lastPage();
        $startPage = max($currentPage - 2, 1);
        $endPage = min($startPage + 4, $lastPage);

        if ($endPage - $startPage < 4) {
            $startPage = max($endPage - 4, 1);
        }

        $pagination = [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $products->total(),
            'has_more_pages' => $products->hasMorePages(),
            'visible_pages' => range($startPage, $endPage),
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $lastPage,
            'previous_page' => $currentPage - 1,
            'next_page' => $currentPage + 1,
        ];


        // Get categories and brands (consider caching these)
        // $categories = ProductCategory::select('id', 'name')->get();
        $brands = Brand::select('id', 'name')->get();

        $products->getCollection()->transform(function ($product) use ($wishlistProductIds) {
                // Handle benefit features
                if (!empty($product->benefit_features)) {
                    $decodedBenefits = json_decode($product->benefit_features, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedBenefits)) {
                        $product->benefit_features = array_map(function ($benefit) {
                            return [
                                'benefit' => $benefit['benefit'] ?? null,
                                'feature' => $benefit['feature'] ?? null,
                            ];
                        }, $decodedBenefits);
                    } else {
                        $product->benefit_features = [];
                    }
                }

                
                if ($product->brand) {
                    $product->brand_id = $product->brand->id;
                    $product->brand_name = $product->brand->name;
                    $product->brand_logo = $product->brand->logo;
                
                    // Get all reviews of all products under this brand
                    $brandReviews = $product->brand->products->flatMap(function ($p) {
                        return $p->reviews;
                    });
                
                    $brandReviewCount = $brandReviews->count();
                    $brandAvgRating = $brandReviewCount > 0
                        ? round($brandReviews->avg('star'), 1)
                        : null;
                
                    $product->brand_avg_rating = $brandAvgRating;
                    $product->brand_review_count = $brandReviewCount;
                }
                

            // Select only required fields for the response
            // $product->images = collect($product->images)->map(function ($image) {
            //     return filter_var($image, FILTER_VALIDATE_URL) ? $image : url('storage/' . ltrim($image, '/'));
            // });

            // $videoPaths = json_decode($product->video_path, true); // Decode JSON to array

            // $product->video_path = collect($videoPaths)->map(function ($video) {
            //     if (filter_var($video, FILTER_VALIDATE_URL)) {
            //         return $video; // If it's already a full URL, return it.
            //     }
            //     return url('storage/' . ltrim($video, '/')); // Manually construct the full URL
            // });

            $product->images = collect($product->images)->map(function ($image) {
                return $image;
            });

            $videoPaths = json_decode($product->video_path, true);
            $product->video_path = collect($videoPaths)->map(function ($video) {
                return $video;
            });
            if ($product->sellingUnitAttribute && $product->sellingUnitAttribute->attribute_value) {
                $fullValue = $product->sellingUnitAttribute->attribute_value;
                if (strpos($fullValue, '/') !== false) {
                    $parts = explode('/', $fullValue);
                    $product->sellingUnitAttribute->attribute_value_unit = trim($parts[1]);
                } else {
                    $product->sellingUnitAttribute->attribute_value_unit = $fullValue;
                }
            }
            
            $totalReviews = $product->reviews->count();
            $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
            $quantity = $product->quantity ?? 0;
            $unitsSold = $product->units_sold ?? 0;
            $leftStock = $quantity - $unitsSold;

            // Prepare the custom response structure
            return [
                'id' => $product->id,
                'name' => $product->name,
                'images' => $product->images,
                'video_url' => $product->video_url,
                'video_path' => $product->video_path,
                'sku' => $product->sku,
                'original_price' => $product->price,
                'front_sale_price' => $product->price,
                'sale_price' => $product->sale_price,
                'price' => $product->price,
                'start_date' => $product->start_date,
                'end_date' => $product->end_date,
                'warranty_information' => $product->warranty_information,
                'currency' => $product->currency ? $product->currency->title : null,
                'total_reviews' => $totalReviews,
                'avg_rating' => $avgRating,
                'best_price' => $product->sale_price ?? $product->price,
                'best_delivery_date' => null, // Customize as needed
                'leftStock' => $leftStock,
                'currency_title' => $product->currency
                    ? ($product->currency->is_prefix_symbol
                        ? $product->currency->title
                        : ($product->price . ' ' . $product->currency->title))
                    : $product->price,
                'in_wishlist' => in_array($product->id, $wishlistProductIds), // Check if the product is in the user's wishlist
            ];
        });

            return response()->json([
                'success' => true,
                'data' => $products,
                'pagination' => $pagination,
                'brands' => $brands,
                // 'categories' => $categories,
                'price_min' => $priceMin,
                'price_max' => $priceMax
             
            ]);
    }


            // public function getAllProductsLisingGuest(Request $request)
            // {
            //     // Start building the query
            //     $query = Product::with('categories', 'brand', 'tags', 'producttypes'); // Ensure 'categories' is included

            //     // Apply filters
            //     $this->applyFilters($query, $request);

            //     // Log the final SQL query for debugging
            //     \Log::info($query->toSql());
            //     \Log::info($query->getBindings());

            //     // Get sort_by parameter
            //     $sortBy = $request->input('sort_by', 'created_at'); // Defaults to 'created_at'

            //     // Validate the sort_by option to avoid any SQL injection
            //     $validSortOptions = ['created_at', 'price', 'name']; // Add other valid fields as needed
            //     if (!in_array($sortBy, $validSortOptions)) {
            //         $sortBy = 'created_at';
            //     }

            //     // Build the query with the specified sort option or default to created_at
            //     $products = Product::orderBy($sortBy, 'desc')->get();

            //     // Get filtered product IDs
            //     $filteredProductIds = $query->pluck('id');

            //     // Calculate min and max values for price, length, width, and height
            //     $priceMin = Product::whereIn('id', $filteredProductIds)->min('sale_price');
            //     $priceMax = Product::whereIn('id', $filteredProductIds)->max('sale_price');
            //     $lengthMin = Product::whereIn('id', $filteredProductIds)->min('length');
            //     $lengthMax = Product::whereIn('id', $filteredProductIds)->max('length');
            //     $widthMin = Product::whereIn('id', $filteredProductIds)->min('width');
            //     $widthMax = Product::whereIn('id', $filteredProductIds)->max('width');
            //     $heightMin = Product::whereIn('id', $filteredProductIds)->min('height');
            //     $heightMax = Product::whereIn('id', $filteredProductIds)->max('height');
            //     $DeliveryMin = Product::whereNotNull('delivery_days')
            //         ->selectRaw('MIN(CAST(delivery_days AS UNSIGNED)) as min_delivery_days')
            //         ->value('min_delivery_days'); // This should return the correct minimum

            //     $DeliveryMax = Product::whereNotNull('delivery_days')
            //         ->selectRaw('MAX(CAST(delivery_days AS UNSIGNED)) as max_delivery_days')
            //         ->value('max_delivery_days'); // This should return the correct maximum

            //     // Subquery for best price and delivery date
            //     $subQuery = Product::select('sku')
            //         ->selectRaw('MIN(price) as best_price')
            //         ->selectRaw('MIN(delivery_days) as best_delivery_date')
            //         ->whereIn('id', $filteredProductIds)
            //         ->groupBy('sku');

            //     // Create the final products query while still respecting previous filters
            //     $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
            //         $join->on('ec_products.sku', '=', 'best_products.sku')
            //             ->whereColumn('ec_products.price', 'best_products.best_price');
            //     })
            //         ->whereIn('id', $filteredProductIds) // Add the filtered IDs back to ensure all filters are respected
            //         ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
            //         ->with('reviews', 'currency', 'specifications') // Including necessary relationships
            //         ->orderBy('created_at', 'desc') // Ensure products are sorted by latest creation date
            //         ->paginate($request->input('per_page', 15)); // Pagination

            //     // Collect unique categories from products
            //     $categories = ProductCategory::select('id', 'name')->get();

            //     // Collect brands
            //     $brands = Brand::select('id', 'name')->get();

            //     // Transform the result to include additional data
            //     $products->getCollection()->transform(function ($product) {
            //         // Select only required fields for the response
            //         $product->images = collect($product->images)->map(function ($image) {
            //             return filter_var($image, FILTER_VALIDATE_URL) ? $image : url('storage/' . ltrim($image, '/'));
            //         });

            //         $totalReviews = $product->reviews->count();
            //         $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
            //         $quantity = $product->quantity ?? 0;
            //         $unitsSold = $product->units_sold ?? 0;
            //         $leftStock = $quantity - $unitsSold;

            //         // Prepare the custom response structure
            //         return [
            //             'id' => $product->id,
            //             'name' => $product->name,
            //             'images' => $product->images,
            //             'video_url' => $product->video_url,
            //             'video_path' => $product->video_path,
            //             'sku' => $product->sku,
            //             'original_price' => $product->price,
            //             'sale_price' => $product->sale_price,
            //             'start_date' => $product->start_date,
            //             'end_date' => $product->end_date,
            //             'warranty_information' => $product->warranty_information,
            //             'currency' => $product->currency ? $product->currency->title : null,
            //             'total_reviews' => $totalReviews,
            //             'avg_rating' => $avgRating,
            //             'best_price' => $product->sale_price ?? $product->price,
            //             'best_delivery_date' => null, // Customize as needed
            //             'leftStock' => $leftStock,
            //             'currency_title' => $product->currency
            //                 ? ($product->currency->is_prefix_symbol
            //                     ? $product->currency->title
            //                     : ($product->price . ' ' . $product->currency->title))
            //                 : $product->price,
            //         ];
            //     });

            //     return response()->json([
            //         'success' => true,
            //         'data' => $products,
            //         'categories' => $categories,
            //         'brands' => $brands,
            //         'price_min' => $priceMin,
            //         'price_max' => $priceMax,
            //         'length_min' => $lengthMin,
            //         'length_max' => $lengthMax,
            //         'width_min' => $widthMin,
            //         'width_max' => $widthMax,
            //         'height_min' => $heightMin,
            //         'height_max' => $heightMax,
            //         'delivery_min' => $DeliveryMin,
            //         'delivery_max' => $DeliveryMax,
            //     ]);
            // }

            public function getAllProductsLisingGuest(Request $request)
            {


                        // Start building the base query
                        $query = Product::with(['categories', 'brand', 'tags', 'producttypes'])
                            ->where('status', 'published');

                        // Apply filters
                        $this->applyFilters($query, $request);

                        // Log query for debugging
                        \Log::info($query->toSql());
                        \Log::info($query->getBindings());

                        // Get filtered IDs efficiently
                        $filteredProductIds = $query->pluck('id');

                        // Calculate min-max values only for filtered products
                        $priceMin = Product::whereIn('id', $filteredProductIds)->min('sale_price');
                        $priceMax = Product::whereIn('id', $filteredProductIds)->max('sale_price');
                      

                        $DeliveryMin = Product::whereIn('id', $filteredProductIds)
                            ->whereNotNull('delivery_days')
                            ->selectRaw('MIN(CAST(delivery_days AS UNSIGNED)) as min_delivery_days')
                            ->value('min_delivery_days');

                        $DeliveryMax = Product::whereIn('id', $filteredProductIds)
                            ->whereNotNull('delivery_days')
                            ->selectRaw('MAX(CAST(delivery_days AS UNSIGNED)) as max_delivery_days')
                            ->value('max_delivery_days');

                        // Get sort parameter
                        $sortBy = $request->input('sort_by', 'created_at');
                        $validSortOptions = ['created_at', 'price', 'name'];
                        if (!in_array($sortBy, $validSortOptions)) {
                            $sortBy = 'created_at';
                        }

                        // Subquery for best price and delivery date
                        $subQuery = Product::select('sku')
                            ->selectRaw('MIN(price) as best_price')
                            ->selectRaw('MIN(delivery_days) as best_delivery_date')
                            ->whereIn('id', $filteredProductIds)
                            ->groupBy('sku');

                        // Paginate efficiently - only get the required number of products
                        $perPage = 50;
                        $page = $request->input('page', 1);

                        $products = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                            $join->on('ec_products.sku', '=', 'best_products.sku')
                                ->whereColumn('ec_products.price', 'best_products.best_price');
                        })
                        ->whereIn('id', $filteredProductIds)
                        ->select('ec_products.*', 'best_products.best_price', 'best_products.best_delivery_date')
                        ->with([
                            'reviews' => function($query) {
                                $query->select('id', 'product_id', 'star');
                            },
                            'currency',
                            'specifications'
                        ])
                        ->orderBy($sortBy, 'desc')
                        ->paginate($perPage);

                        // Add query parameters to pagination
                        $products->appends($request->all());

                        // Calculate pagination details
                        $currentPage = $products->currentPage();
                        $lastPage = $products->lastPage();
                        $startPage = max($currentPage - 2, 1);
                        $endPage = min($startPage + 4, $lastPage);

                        if ($endPage - $startPage < 4) {
                            $startPage = max($endPage - 4, 1);
                        }

                        $pagination = [
                            'current_page' => $currentPage,
                            'last_page' => $lastPage,
                            'per_page' => $perPage,
                            'total' => $products->total(),
                            'has_more_pages' => $products->hasMorePages(),
                            'visible_pages' => range($startPage, $endPage),
                            'has_previous' => $currentPage > 1,
                            'has_next' => $currentPage < $lastPage,
                            'previous_page' => $currentPage - 1,
                            'next_page' => $currentPage + 1,
                        ];

                        // Get categories and brands (consider caching these)
                        // $categories = ProductCategory::select('id', 'name')->get();
                        $brands = Brand::select('id', 'name')->get();

                        $products->getCollection()->transform(function ($product)  {

                            // Select only required fields for the response
                            // $product->images = collect($product->images)->map(function ($image) {
                            //     return filter_var($image, FILTER_VALIDATE_URL) ? $image : url('storage/' . ltrim($image, '/'));
                            // });

                            // $videoPaths = json_decode($product->video_path, true); // Decode JSON to array

                            // $product->video_path = collect($videoPaths)->map(function ($video) {
                            //     if (filter_var($video, FILTER_VALIDATE_URL)) {
                            //         return $video; // If it's already a full URL, return it.
                            //     }
                            //     return url('storage/' . ltrim($video, '/')); // Manually construct the full URL
                            // });

                            $product->images = collect($product->images)->map(function ($image) {
                                return $image;
                            });

                            $videoPaths = json_decode($product->video_path, true);
                            $product->video_path = collect($videoPaths)->map(function ($video) {
                                return $video;
                            });
                            if ($product->sellingUnitAttribute && $product->sellingUnitAttribute->attribute_value) {
                                $fullValue = $product->sellingUnitAttribute->attribute_value;
                                if (strpos($fullValue, '/') !== false) {
                                    $parts = explode('/', $fullValue);
                                    $product->sellingUnitAttribute->attribute_value_unit = trim($parts[1]);
                                } else {
                                    $product->sellingUnitAttribute->attribute_value_unit = $fullValue;
                                }
                            }
                            
                            $totalReviews = $product->reviews->count();
                            $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
                            $quantity = $product->quantity ?? 0;
                            $unitsSold = $product->units_sold ?? 0;
                            $leftStock = $quantity - $unitsSold;

                            // Prepare the custom response structure
                            return [
                                'id' => $product->id,
                                'name' => $product->name,
                                'images' => $product->images,
                                'video_url' => $product->video_url,
                                'video_path' => $product->video_path,
                                'sku' => $product->sku,
                                'original_price' => $product->price,
                                'front_sale_price' => $product->price,
                                'sale_price' => $product->sale_price,
                                'price' => $product->price,
                                'start_date' => $product->start_date,
                                'end_date' => $product->end_date,
                                'warranty_information' => $product->warranty_information,
                                'currency' => $product->currency ? $product->currency->title : null,
                                'total_reviews' => $totalReviews,
                                'avg_rating' => $avgRating,
                                'best_price' => $product->sale_price ?? $product->price,
                                'best_delivery_date' => null, // Customize as needed
                                'leftStock' => $leftStock,
                                'currency_title' => $product->currency
                                    ? ($product->currency->is_prefix_symbol
                                        ? $product->currency->title
                                        : ($product->price . ' ' . $product->currency->title))
                                    : $product->price,

                            ];
                        });

                            return response()->json([
                                'success' => true,
                                'data' => $products,
                                'pagination' => $pagination,
                                'brands' => $brands,
                                // 'categories' => $categories,
                                'price_min' => $priceMin,
                                'price_max' => $priceMax
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

        
            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->input('is_featured'));
            }

            if ($request->has('is_variation')) {
                $query->where('is_variation', $request->input('is_variation'));
            }
            if ($request->has('variant_fulfillment_service')) {
                $query->where('variant_fulfillment_service', $request->input('variant_fulfillment_service'));
            }

            if ($request->has('variant_requires_shipping')) {
                $query->where('variant_requires_shipping', $request->input('variant_requires_shipping'));
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

                    // Apply the filter using brand IDs sd
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

//         protected function applyFilters($query, Request $request)
// {
//     // Price filter
//     if ($request->has('price_min') && $request->has('price_max')) {
//         $query->whereBetween('price', [$request->price_min, $request->price_max]);
//     }

//     // Length filter
//     if ($request->has('length_min') && $request->has('length_max')) {
//         $query->whereBetween('length', [$request->length_min, $request->length_max]);
//     }

//     // Width filter
//     if ($request->has('width_min') && $request->has('width_max')) {
//         $query->whereBetween('width', [$request->width_min, $request->width_max]);
//     }

//     // Height filter
//     if ($request->has('height_min') && $request->has('height_max')) {
//         $query->whereBetween('height', [$request->height_min, $request->height_max]);
//     }

//     // Delivery Days filter
//     if ($request->has('delivery_min') && $request->has('delivery_max')) {
//         $query->whereRaw('CAST(delivery_days AS UNSIGNED) BETWEEN ? AND ?',
//             [$request->delivery_min, $request->delivery_max]);
//     }

//     // Category filter
//     if ($request->has('category')) {
//         $categoryIds = explode(',', $request->category);
//         $query->whereHas('categories', function($q) use ($categoryIds) {
//             $q->whereIn('categories.id', $categoryIds);
//         });
//     }

//     // Brand filter
//     if ($request->has('brand')) {
//         $brandIds = explode(',', $request->brand);
//         $query->whereIn('brand_id', $brandIds);
//     }

//     // Search by name
//     if ($request->has('search')) {
//         $searchTerm = $request->search;
//         $query->where(function($q) use ($searchTerm) {
//             $q->where('name', 'LIKE', "%{$searchTerm}%")
//               ->orWhere('description', 'LIKE', "%{$searchTerm}%")
//               ->orWhere('sku', 'LIKE', "%{$searchTerm}%");
//         });
//     }

//     // Product Type filter
//     if ($request->has('product_type')) {
//         $productTypeIds = explode(',', $request->product_type);
//         $query->whereHas('producttypes', function($q) use ($productTypeIds) {
//             $q->whereIn('product_types.id', $productTypeIds);
//         });
//     }

//     // Tags filter
//     if ($request->has('tags')) {
//         $tagIds = explode(',', $request->tags);
//         $query->whereHas('tags', function($q) use ($tagIds) {
//             $q->whereIn('ec_product_tags.id', $tagIds);
//         });
//     }

//     // Stock status filter
//     if ($request->has('stock_status')) {
//         $stockStatus = $request->stock_status;
//         if ($stockStatus === 'in_stock') {
//             $query->where('quantity', '>', 0);
//         } elseif ($stockStatus === 'out_of_stock') {
//             $query->where('quantity', '<=', 0);
//         }
//     }

//     // Sort by rating
//     if ($request->has('rating')) {
//         $minRating = $request->rating;
//         $query->whereHas('reviews', function($q) use ($minRating) {
//             $q->select('product_id')
//               ->groupBy('product_id')
//               ->havingRaw('AVG(star) >= ?', [$minRating]);
//         });
//     }

//     // Price range filter (alternative method)
//     if ($request->has('price_range')) {
//         $ranges = explode(',', $request->price_range);
//         $query->where(function($q) use ($ranges) {
//             foreach ($ranges as $range) {
//                 list($min, $max) = explode('-', $range);
//                 $q->orWhereBetween('price', [$min, $max]);
//             }
//         });
//     }

//     // Featured products filter
//     if ($request->has('featured')) {
//         $query->where('is_featured', true);
//     }

//     // New arrivals filter (products added in last 30 days)
//     if ($request->has('new_arrivals')) {
//         $thirtyDaysAgo = now()->subDays(30);
//         $query->where('created_at', '>=', $thirtyDaysAgo);
//     }

//     // On sale filter
//     if ($request->has('on_sale')) {
//         $query->whereNotNull('sale_price')
//               ->where('sale_price', '<', DB::raw('price'));
//     }

//     return $query;
// }



public function relatedProducts($id)
{
    $product = Product::find($id);

    if (!$product) {
        return response()->json(['message' => 'Product not found'], 404);
    }

    // Auth and wishlist logic
    $userId = Auth::id();
    $wishlistProductIds = [];

    if ($userId) {
        $wishlistProductIds = DB::table('ec_wish_lists')
            ->where('customer_id', $userId)
            ->pluck('product_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    } else {
        $wishlistProductIds = session()->get('guest_wishlist', []);
    }

    // Get related categories
    $categoryIds = $product->categories->pluck('id');

    if ($categoryIds->isEmpty()) {
        return response()->json(['message' => 'No related categories found'], 404);
    }

    $relatedProducts = Product::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })
        ->where('id', '!=', $id)
        ->where('status', 'published')
        ->inRandomOrder()
        ->limit(20)
        ->with([
            'reviews:id,product_id,star',
            'currency',
            'specifications'
        ])
        ->get();

    $transformed = $relatedProducts->map(function ($product) use ($wishlistProductIds) {
        // $product->images = collect($product->images)->map(function ($image) {
        //     return filter_var($image, FILTER_VALIDATE_URL) ? $image : url('storage/' . ltrim($image, '/'));
        // });

        // $videoPaths = json_decode($product->video_path, true) ?? [];
        // $product->video_path = collect($videoPaths)->map(function ($video) {
        //     return filter_var($video, FILTER_VALIDATE_URL) ? $video : url('storage/' . ltrim($video, '/'));
        // });
         $product->images = collect($product->images)->map(function ($image) {
                    return $image;
                });

                $videoPaths = json_decode($product->video_path, true);
                $product->video_path = collect($videoPaths)->map(function ($video) {
                    return $video;
                });


        $totalReviews = $product->reviews->count();
        $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
        $quantity = $product->quantity ?? 0;
        $unitsSold = $product->units_sold ?? 0;
        $leftStock = $quantity - $unitsSold;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'images' => $product->images,
            'video_url' => $product->video_url,
            'video_path' => $product->video_path,
            'sku' => $product->sku,
            'original_price' => $product->price,
            'sale_price' => $product->sale_price,
            'front_sale_price' => $product->sale_price ?? $product->price,
            'price' => $product->price,
            'start_date' => $product->start_date,
            'end_date' => $product->end_date,
            'warranty_information' => $product->warranty_information,
            'currency' => $product->currency?->title,
            'total_reviews' => $totalReviews,
            'avg_rating' => $avgRating,
            'best_price' => $product->sale_price ?? $product->price,
            'best_delivery_date' => null, // optional to calculate
            'leftStock' => $leftStock,
            'currency_title' => $product->currency
                ? ($product->currency->is_prefix_symbol
                    ? $product->currency->title
                    : ($product->price . ' ' . $product->currency->title))
                : $product->price,
            'in_wishlist' => in_array($product->id, $wishlistProductIds),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $transformed
    ]);
}

public function productsByBrand($id, Request $request)
{
    $brand = Brand::find($id);

    if (!$brand) {
        return response()->json([
            'success' => false,
            'message' => 'Brand not found',
        ], 404);
    }

    $perPage = $request->get('per_page', 10);

    // Auth and wishlist logic
    $userId = Auth::id();
    $wishlistProductIds = [];

    if ($userId) {
        $wishlistProductIds = DB::table('ec_wish_lists')
            ->where('customer_id', $userId)
            ->pluck('product_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    } else {
        $wishlistProductIds = session()->get('guest_wishlist', []);
    }

    // Get paginated products with relationships
    $products = $brand->products()
        ->where('status', 'published')
        ->with(['reviews:id,product_id,star', 'currency', 'specifications'])
        ->paginate($perPage);

    // Transform each product
    $transformed = collect($products->items())->map(function ($product) use ($wishlistProductIds) {
        // $product->images = collect($product->images)->map(function ($image) {
        //     return filter_var($image, FILTER_VALIDATE_URL) ? $image : url('storage/' . ltrim($image, '/'));
        // });

        // $videoPaths = json_decode($product->video_path, true) ?? [];
        // $product->video_path = collect($videoPaths)->map(function ($video) {
        //     return filter_var($video, FILTER_VALIDATE_URL) ? $video : url('storage/' . ltrim($video, '/'));
        // });
        $product->images = collect($product->images)->map(function ($image) {
            return $image;
        });

        $videoPaths = json_decode($product->video_path, true);
        $product->video_path = collect($videoPaths)->map(function ($video) {
            return $video;
        });


        $totalReviews = $product->reviews->count();
        $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
        $quantity = $product->quantity ?? 0;
        $unitsSold = $product->units_sold ?? 0;
        $leftStock = $quantity - $unitsSold;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'images' => $product->images,
            'video_url' => $product->video_url,
            'video_path' => $product->video_path,
            'sku' => $product->sku,
            'original_price' => $product->price,
            'sale_price' => $product->sale_price,
            'front_sale_price' => $product->sale_price ?? $product->price,
            'price' => $product->price,
            'start_date' => $product->start_date,
            'end_date' => $product->end_date,
            'warranty_information' => $product->warranty_information,
            'currency' => $product->currency?->title,
            'total_reviews' => $totalReviews,
            'avg_rating' => $avgRating,
            'best_price' => $product->sale_price ?? $product->price,
            'best_delivery_date' => null,
            'leftStock' => $leftStock,
            'currency_title' => $product->currency
                ? ($product->currency->is_prefix_symbol
                    ? $product->currency->title
                    : ($product->price . ' ' . $product->currency->title))
                : $product->price,
            'in_wishlist' => in_array($product->id, $wishlistProductIds),
        ];
    });

    return response()->json([
        'success' => true,
        'message' => 'Products fetched successfully',
        'current_page' => $products->currentPage(),
        'last_page' => $products->lastPage(),
        'total' => $products->total(),
        'per_page' => $products->perPage(),
        'data' => $transformed,
    ]);
}


public function saleProductsByBrand($id, Request $request)
{
    $brand = Brand::find($id);

    if (!$brand) {
        return response()->json([
            'success' => false,
            'message' => 'Brand not found',
        ], 404);
    }

    $perPage = $request->get('per_page', 10);

    // Wishlist logic
    $userId = Auth::id();
    $wishlistProductIds = [];

    if ($userId) {
        $wishlistProductIds = DB::table('ec_wish_lists')
            ->where('customer_id', $userId)
            ->pluck('product_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    } else {
        $wishlistProductIds = session()->get('guest_wishlist', []);
    }

    // Fetch products with non-empty sale_price
    $products = $brand->products()
        ->where('status', 'published')
        ->whereNotNull('sale_price')
        ->where('sale_price', '>', 0)
        ->with(['reviews:id,product_id,star', 'currency', 'specifications'])
        ->paginate($perPage);

    $transformed = collect($products->items())->map(function ($product) use ($wishlistProductIds) {
        // $product->images = collect($product->images)->map(function ($image) {
        //     return filter_var($image, FILTER_VALIDATE_URL) ? $image : url('storage/' . ltrim($image, '/'));
        // });

        // $videoPaths = json_decode($product->video_path, true) ?? [];
        // $product->video_path = collect($videoPaths)->map(function ($video) {
        //     return filter_var($video, FILTER_VALIDATE_URL) ? $video : url('storage/' . ltrim($video, '/'));
        // });

        $product->images = collect($product->images)->map(function ($image) {
         return $image;
            });

            $videoPaths = json_decode($product->video_path, true);
            $product->video_path = collect($videoPaths)->map(function ($video) {
                return $video;
            });
        $totalReviews = $product->reviews->count();
        $avgRating = $totalReviews > 0 ? $product->reviews->avg('star') : null;
        $quantity = $product->quantity ?? 0;
        $unitsSold = $product->units_sold ?? 0;
        $leftStock = $quantity - $unitsSold;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'images' => $product->images,
            'video_url' => $product->video_url,
            'video_path' => $product->video_path,
            'sku' => $product->sku,
            'original_price' => $product->price,
            'sale_price' => $product->sale_price,
            'front_sale_price' => $product->sale_price ?? $product->price,
            'price' => $product->price,
            'start_date' => $product->start_date,
            'end_date' => $product->end_date,
            'warranty_information' => $product->warranty_information,
            'currency' => $product->currency?->title,
            'total_reviews' => $totalReviews,
            'avg_rating' => $avgRating,
            'best_price' => $product->sale_price ?? $product->price,
            'best_delivery_date' => null,
            'leftStock' => $leftStock,
            'currency_title' => $product->currency
                ? ($product->currency->is_prefix_symbol
                    ? $product->currency->title
                    : ($product->price . ' ' . $product->currency->title))
                : $product->price,
            'in_wishlist' => in_array($product->id, $wishlistProductIds),
        ];
    });

    return response()->json([
        'success' => true,
        'message' => 'Sale products fetched successfully',
        'current_page' => $products->currentPage(),
        'last_page' => $products->lastPage(),
        'total' => $products->total(),
        'per_page' => $products->perPage(),
        'data' => $transformed,
    ]);
}


public function brandSummaryStats($id)
{
    $brand = Brand::find($id);

    if (!$brand) {
        return response()->json([
            'success' => false,
            'message' => 'Brand not found',
        ], 404);
    }

    $productIds = Product::where('brand_id', $id)->pluck('id');

    $totalUnitsSold = Product::whereIn('id', $productIds)->sum('units_sold');

    $totalReviews = DB::table('ec_reviews')
        ->whereIn('product_id', $productIds)
        ->count();

    return response()->json([
        'success' => true,
        'message' => 'Brand summary fetched successfully',
        'data' => [
            'brand_id' => $id,
            'brand_name' => $brand->name,
            'total_units_sold' => $totalUnitsSold,
            'total_reviews' => $totalReviews,
        ]
    ]);
}






}
