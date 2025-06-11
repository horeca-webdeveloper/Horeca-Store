<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Botble\Ecommerce\Models\Brand;
use Illuminate\Support\Str;
use Botble\Ecommerce\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BrandApiController extends Controller
{
 
    private function getWishlistProductIds()
    {
        $userId = Auth::id();

        if ($userId) {
            return Cache::remember("wishlist_user_{$userId}", 60, function () use ($userId) {
                return DB::table('ec_wish_lists')
                    ->where('customer_id', $userId)
                    ->pluck('product_id')
                    ->toArray();
            });
        }

        return session()->get('guest_wishlist', []);
    }

   
    public function getAllHomeBrandProducts(Request $request)
    {
        $wishlistIds = $this->getWishlistProductIds();

        // Fetch only the latest 5 brands with at least 10 products
        $brands = Brand::with(['products'])
            ->whereHas('products', function ($query) {
                $query->select('brand_id') // Select only the column needed for grouping
                    ->groupBy('brand_id') // Group by the brand_id
                    ->havingRaw('COUNT(*) >= 10'); // Ensure the brand has at least 10 products
            })
            ->orderBy('created_at', 'desc') // Order by latest brands
            ->take(5) // Limit to 5 brands
            ->get();

        return response()->json([
            'success' => true,
            'data' => $brands->map(function ($brand) use ($request, $wishlistIds) {
                // Filter and limit products to 10 for each brand
                $products = $brand->products()
                    ->when($request->has('search'), function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->input('search') . '%');
                    })
                    ->when($request->has('price_min'), function ($query) use ($request) {
                        $query->where('price', '>=', $request->input('price_min'));
                    })
                    ->when($request->has('price_max'), function ($query) use ($request) {
                        $query->where('price', '<=', $request->input('price_max'));
                    })
                    ->when($request->has('rating'), function ($query) use ($request) {
                        $query->whereHas('reviews', function ($q) use ($request) {
                            $q->selectRaw('AVG(star) as avg_rating')
                                ->groupBy('product_id')
                                ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
                        });
                    })
                    ->orderBy('created_at', 'desc') // Order products by latest
                    ->take(10) // Limit to 10 products per brand
                    ->get();

                // Map brand data
                return [
                    'brand_name' => $brand->name,
                    'products' => $products->map(function ($product) use ($wishlistIds) {
                        // Improved logic to construct image URLs
                        $getImageUrl = function ($imageName) {
                            if (Str::startsWith($imageName, ['http://', 'https://'])) {
                                return $imageName; // Return the full URL if already valid
                            }

                            $paths = [
                                "storage/products/{$imageName}",
                                "storage/{$imageName}",
                            ];

                            foreach ($paths as $path) {
                                if (file_exists(public_path($path))) {
                                    return asset($path);
                                }
                            }

                            return asset('images/default.png'); // Default image if none found
                        };

                        $productImages = is_array($product->images) ? $product->images : ($product->images ? $product->images->toArray() : []);

                        // Then use safe access:
                    $sellingUnit = $product->sellingUnitAttribute?->attribute_value_unit ?? 'N/A';

                    // Or process the attribute value safely:
                    if ($product->sellingUnitAttribute && $product->sellingUnitAttribute->attribute_value) {
                        $fullValue = $product->sellingUnitAttribute->attribute_value;
                        if (strpos($fullValue, '/') !== false) {
                            $parts = explode('/', $fullValue);
                            $sellingUnit = trim($parts[1]);
                        } else {
                            $sellingUnit = $fullValue;
                        }
                    } else {
                        $sellingUnit = null;
                    }


                        return [
                            "id" => $product->id,
                            // "name" => $product->name,
                            // "images" => array_map(function ($image) use ($getImageUrl) {
                            //     return $getImageUrl($image);
                            // }, $productImages),
                            // "sku" => $product->sku ?? '',
                            // "price" => $product->price,
                            // "sale_price" => $product->sale_price ?? null,
                            // "rating" => $product->reviews()->avg('star') ?? null,
                            // "in_wishlist" => in_array($product->id, $wishlistIds),
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'price' => $product->price,
                            'sale_price' => $product->sale_price,
                            'best_delivery_date' => $product->best_delivery_date,
                            'total_reviews' => $product->reviews->count(),
                            'avg_rating' => $product->reviews->count() > 0 ? $product->reviews->avg('star') : null,
                            'left_stock' => $product->left_stock ?? 0,
                            'currency' => $product->currency->title ?? 'USD',
                            'in_wishlist' => $product->in_wishlist ?? false,
                            'images' => collect($product->images)->map(function ($image) {
                                if (filter_var($image, FILTER_VALIDATE_URL)) {
                                    return $image;
                                }
                                $baseUrl = (strpos($image, 'storage/products/') === 0) ? url('storage/products/') : url('storage/');
                                return $baseUrl . '/' . ltrim($image, '/');
                            })->toArray(),
                            'original_price' => $product->price,
                            'front_sale_price' => $product->price,
                            'best_price' => $product->price,
                            'selling_unit' => $sellingUnit
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function getAllBrandGuestProducts(Request $request)
    {
        // Subquery for best price and delivery days by SKU
        $subQuery = Product::select('sku')
            ->selectRaw('MIN(price) as best_price')
            ->selectRaw('MIN(delivery_days) as best_delivery_date')
            ->groupBy('sku');

        // Fetch only the latest 5 brands with at least 10 products
        $brands = Brand::with(['products'])
            ->whereHas('products', function ($query) {
                $query->select('brand_id')
                    ->groupBy('brand_id')
                    ->havingRaw('COUNT(*) >= 10');
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $brands->map(function ($brand) use ($request, $subQuery) {
                // Filter and limit products to 10 for each brand
                $products = $brand->products()
                    ->when($request->has('search'), function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->input('search') . '%');
                    })
                    ->when($request->has('price_min'), function ($query) use ($request) {
                        $query->where('price', '>=', $request->input('price_min'));
                    })
                    ->when($request->has('price_max'), function ($query) use ($request) {
                        $query->where('price', '<=', $request->input('price_max'));
                    })
                    ->when($request->has('rating'), function ($query) use ($request) {
                        $query->whereHas('reviews', function ($q) use ($request) {
                            $q->selectRaw('AVG(star) as avg_rating')
                                ->groupBy('product_id')
                                ->havingRaw('AVG(star) >= ?', [$request->input('rating')]);
                        });
                    })
                    ->take(10)
                    ->pluck('id'); // Only get product IDs

                // Fetch product details with joined best_price and eager load
                $productDetails = Product::leftJoinSub($subQuery, 'best_products', function ($join) {
                        $join->on('ec_products.sku', '=', 'best_products.sku')
                            ->whereColumn('ec_products.price', 'best_products.best_price');
                    })
                    ->whereIn('ec_products.id', $products)
                    ->with(['reviews', 'currency', 'sellingUnitAttribute'])
                    ->get()
                    ->keyBy('id');

                return [
                    'brand_name' => $brand->name,
                    'products' => $productDetails->map(function ($details) {
                        $totalReviews = $details->reviews->count();
                        $avgRating = $totalReviews > 0 ? $details->reviews->avg('star') : null;
                        $leftStock = ($details->quantity ?? 0) - ($details->units_sold ?? 0);
                        $currencyTitle = $details->currency->title ?? $details->price;

                        $imageUrls = collect($details->images)->map(fn($image) =>
                            Str::startsWith($image, ['http://', 'https://']) ? $image : asset('storage/' . ltrim($image, '/'))
                        );

                        // Then use safe access:
                    $sellingUnit = $details->sellingUnitAttribute?->attribute_value_unit ?? 'N/A';

                    // Or process the attribute value safely:
                    if ($details->sellingUnitAttribute && $details->sellingUnitAttribute->attribute_value) {
                        $fullValue = $details->sellingUnitAttribute->attribute_value;
                        if (strpos($fullValue, '/') !== false) {
                            $parts = explode('/', $fullValue);
                            $sellingUnit = trim($parts[1]);
                        } else {
                            $sellingUnit = $fullValue;
                        }
                    } else {
                        $sellingUnit = null;
                    }


                        return [
                            'id' => $details->id,
                            'name' => $details->name,
                            'sku' => $details->sku,
                            'price' => $details->best_price ?? $details->price,
                            'original_price' => $details->price,
                            'sale_price' => $details->sale_price,
                            'best_delivery_date' => $details->best_delivery_date,
                            'total_reviews' => $totalReviews,
                            'avg_rating' => $avgRating,
                            'left_stock' => $leftStock,
                            'currency' => $currencyTitle,
                            'images' => $imageUrls,
                            'front_sale_price' => $details->price,
                            'best_price' => $details->best_price ?? $details->price,
                            'selling_unit' => $sellingUnit
                        ];
                    })->values(),
                ];
            }),
        ]);
    }

    public function brandsByCategory($id): JsonResponse
    {
        $brandIds = Product::whereHas('categories', function ($query) use ($id) {
            $query->where('ec_product_category_product.category_id', $id);
        })->pluck('brand_id')->unique()->filter();

        if ($brandIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No brands found for this category.',
                'data' => []
            ], 404);
        }

        $brands = Brand::whereIn('id', $brandIds)
            ->where('status', 'published')
            ->select('id', 'name', 'logo')
            ->get()
            ->map(function ($brand) {
                $brand->logo = $brand->logo ? asset( $brand->logo) : null;
                return $brand;
            });

        return response()->json([
            'success' => true,
            'message' => 'Brands retrieved successfully.',
            'data' => $brands
        ]);
    }

    public function getCategories($id)
    {
        $brand = Brand::with(['products.categories'])->findOrFail($id);
    
        // Count the number of products per category for this brand
        $categoryCounts = [];
    
        foreach ($brand->products as $product) {
            foreach ($product->categories as $category) {
                // Only include categories where status is 'published'
                if ($category->status !== 'published') {
                    continue;
                }
        
                if (!isset($categoryCounts[$category->id])) {
                    $categoryCounts[$category->id] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'image' => asset('storage/' . $category->image),
                        'product_count' => 0
                    ];
                }
        
                $categoryCounts[$category->id]['product_count']++;
            }
        }
    
        // Reindex array and return as values
        $categories = array_values($categoryCounts);
    
        return response()->json([
            'success' => true,
            'brand_id' => $id,
            'categories' => $categories
        ]);
    }
  
    //  public function getProductsByBrandAndCategory(Request $request, $brandId, $categoryId = null)
    //  {
    //         try {
    //             // Load only published products with published categories
    //             $brand = Brand::with(['products' => function ($query) {
    //                 $query->where('status', 'published')
    //                       ->whereHas('categories', function ($q) {
    //                           $q->where('status', 'published');
    //                       });
    //             }, 'products.categories' => function ($query) {
    //                 $query->where('status', 'published');
    //             }])->findOrFail($brandId);
        
    //             // Filter by category if provided
    //             $filteredProducts = is_null($categoryId)
    //                 ? $brand->products
    //                 : $brand->products->filter(function ($product) use ($categoryId) {
    //                     return $product->categories->contains(function ($category) use ($categoryId) {
    //                         return $category->id == $categoryId && $category->status === 'published';
    //                     });
    //                 })->values();
        
    //             // Apply search if provided
    //             if ($search = $request->input('search')) {
    //                 $filteredProducts = $filteredProducts->filter(function ($product) use ($search) {
    //                     return str_contains(strtolower($product->name), strtolower($search)) ||
    //                            str_contains(strtolower($product->sku), strtolower($search));
    //                 })->values();
    //             }
        
    //             if ($filteredProducts->isEmpty()) {
    //                 return response()->json([
    //                     'success' => true,
    //                     'message' => 'No products found for this brand' . ($categoryId ? ' and category' : '') . ($search ? ' matching search' : ''),
    //                     'data' => [],
    //                     'pagination' => $this->emptyPagination(),
    //                 ]);
    //             }
        
    //          $productIds = $filteredProducts->pluck('id')->toArray();
     
    //          $productsWithRelations = Product::whereIn('id', $productIds)
    //              ->with([
    //                  'reviews:id,product_id,star',
    //                  'currency',
    //                  'specifications',
    //              ])
    //              ->get()
    //              ->keyBy('id');
     
    //          $perPage = 50;
    //          $page = max(1, (int) $request->input('page', 1));
    //          $total = count($productIds);
    //          $offset = ($page - 1) * $perPage;
    //          $paginatedProducts = $filteredProducts->slice($offset, $perPage);
     
    //          $pagination = $this->buildPagination($page, $perPage, $total);
     
    //          $transformedProducts = $paginatedProducts->map(function ($product) use ($productsWithRelations) {
    //              $productWithRelations = $productsWithRelations->get($product->id) ?? $product;
     
    //              $images = $this->normalizeMediaUrls($product->images);
    //              $videos = $this->normalizeMediaUrls($product->video_path);
     
    //              $totalReviews = $productWithRelations->reviews ? $productWithRelations->reviews->count() : 0;
    //              $avgRating = $totalReviews > 0 ? $productWithRelations->reviews->avg('star') : null;
     
    //              $quantity = $product->quantity ?? 0;
    //              $unitsSold = $product->units_sold ?? 0;
    //              $leftStock = $quantity - $unitsSold;
     
    //              return [
    //                  'id' => $product->id,
    //                  'name' => $product->name,
    //                  'images' => $images,
    //                  'video_url' => $product->video_url,
    //                  'video_path' => $videos,
    //                  'sku' => $product->sku,
    //                  'original_price' => $product->price,
    //                  'front_sale_price' => $product->price,
    //                  'sale_price' => $product->sale_price,
    //                  'price' => $product->price,
    //                  'start_date' => $product->start_date,
    //                  'end_date' => $product->end_date,
    //                  'warranty_information' => $product->warranty_information,
    //                  'currency' => $productWithRelations->currency?->title,
    //                  'total_reviews' => $totalReviews,
    //                  'avg_rating' => $avgRating,
    //                  'best_price' => $product->sale_price ?? $product->price,
    //                  'best_delivery_date' => null,
    //                  'leftStock' => $leftStock,
    //                  'currency_title' => $productWithRelations->currency
    //                      ? ($productWithRelations->currency->is_prefix_symbol
    //                          ? $productWithRelations->currency->title
    //                          : ($product->price . ' ' . $productWithRelations->currency->title))
    //                      : $product->price,
    //              ];
    //          });
     
    //          return response()->json([
    //              'success' => true,
    //              'data' => $transformedProducts->values(),
    //              'pagination' => $pagination,
    //              'message' => 'Products retrieved successfully',
    //          ]);
    //      } catch (\Exception $e) {
    //          Log::error('Error in getProductsByBrandAndCategory: ' . $e->getMessage());
    //          return response()->json([
    //              'success' => false,
    //              'message' => 'An error occurred while fetching products',
    //              'error' => $e->getMessage(),
    //          ], 500);
    //      }
    //  }
    // public function getProductsByBrandAndCategory(Request $request, $brandId, $categoryId = null)
    // {
    //     try {
    //         $brand = Brand::with(['products.categories'])->findOrFail($brandId);

    //         // Get all brand products or filter by category
    //         $filteredProducts = is_null($categoryId)
    //             ? $brand->products
    //             : $brand->products->filter(function ($product) use ($categoryId) {
    //                 return $product->categories->contains('id', $categoryId);
    //             })->values();

    //         if ($filteredProducts->isEmpty()) {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'No products found for this brand' . ($categoryId ? ' and category' : ''),
    //                 'data' => [],
    //                 'pagination' => $this->emptyPagination(),
    //             ]);
    //         }

    //         $productIds = $filteredProducts->pluck('id')->toArray();

    //         $productsWithRelations = Product::whereIn('id', $productIds)
    //             ->with([
    //                 'reviews:id,product_id,star',
    //                 'currency',
    //                 'specifications',
    //             ])
    //             ->get()
    //             ->keyBy('id');

    //         $perPage = 50;
    //         $page = max(1, (int) $request->input('page', 1));
    //         $total = count($productIds);
    //         $offset = ($page - 1) * $perPage;
    //         $paginatedProducts = $filteredProducts->slice($offset, $perPage);

    //         $pagination = $this->buildPagination($page, $perPage, $total);

    //         $transformedProducts = $paginatedProducts->map(function ($product) use ($productsWithRelations) {
    //             $productWithRelations = $productsWithRelations->get($product->id) ?? $product;

    //             $images = $this->normalizeMediaUrls($product->images);
    //             $videos = $this->normalizeMediaUrls($product->video_path);

    //             $totalReviews = $productWithRelations->reviews ? $productWithRelations->reviews->count() : 0;
    //             $avgRating = $totalReviews > 0 ? $productWithRelations->reviews->avg('star') : null;

    //             $quantity = $product->quantity ?? 0;
    //             $unitsSold = $product->units_sold ?? 0;
    //             $leftStock = $quantity - $unitsSold;

    //             return [
    //                 'id' => $product->id,
    //                 'name' => $product->name,
    //                 'images' => $images,
    //                 'video_url' => $product->video_url,
    //                 'video_path' => $videos,
    //                 'sku' => $product->sku,
    //                 'original_price' => $product->price,
    //                 'front_sale_price' => $product->price,
    //                 'sale_price' => $product->sale_price,
    //                 'price' => $product->price,
    //                 'start_date' => $product->start_date,
    //                 'end_date' => $product->end_date,
    //                 'warranty_information' => $product->warranty_information,
    //                 'currency' => $productWithRelations->currency?->title,
    //                 'total_reviews' => $totalReviews,
    //                 'avg_rating' => $avgRating,
    //                 'best_price' => $product->sale_price ?? $product->price,
    //                 'best_delivery_date' => null,
    //                 'leftStock' => $leftStock,
    //                 'currency_title' => $productWithRelations->currency
    //                     ? ($productWithRelations->currency->is_prefix_symbol
    //                         ? $productWithRelations->currency->title
    //                         : ($product->price . ' ' . $productWithRelations->currency->title))
    //                     : $product->price,
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'data' => $transformedProducts->values(),
    //             'pagination' => $pagination,
    //             'message' => 'Products retrieved successfully',
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Error in getProductsByBrandAndCategory: ' . $e->getMessage());
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'An error occurred while fetching products',
    //         'error' => $e->getMessage(),
    //     ], 500);
    //   }
    // }
    public function getProductsByBrandAndCategory(Request $request, $brandId, $categoryId = null)
    {
        try {
            $searchTerm = strtolower($request->input('search'));

            $brand = Brand::with(['products.categories'])->findOrFail($brandId);

            // Filter by category
            $filteredProducts = is_null($categoryId)
                ? $brand->products
                : $brand->products->filter(function ($product) use ($categoryId) {
                    return $product->categories->contains('id', $categoryId);
                })->values();

            // Filter by search term if provided
            if (!empty($searchTerm)) {
                $filteredProducts = $filteredProducts->filter(function ($product) use ($searchTerm) {
                    return stripos($product->name, $searchTerm) !== false;
                })->values();
            }

            if ($filteredProducts->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No products found for this brand' . ($categoryId ? ' and category' : '') . ($searchTerm ? ' with search term' : ''),
                    'data' => [],
                    'pagination' => $this->emptyPagination(),
                ]);
            }

            $productIds = $filteredProducts->pluck('id')->toArray();

            $productsWithRelations = Product::whereIn('id', $productIds)
                ->with([
                    'reviews:id,product_id,star',
                    'currency',
                    'specifications',
                ])
                ->get()
                ->keyBy('id');

            $perPage = 50;
            $page = max(1, (int) $request->input('page', 1));
            $total = count($productIds);
            $offset = ($page - 1) * $perPage;
            $paginatedProducts = $filteredProducts->slice($offset, $perPage);

            $pagination = $this->buildPagination($page, $perPage, $total);

            $transformedProducts = $paginatedProducts->map(function ($product) use ($productsWithRelations) {
                $productWithRelations = $productsWithRelations->get($product->id) ?? $product;

                $images = $this->normalizeMediaUrls($product->images);
                $videos = $this->normalizeMediaUrls($product->video_path);

                $totalReviews = $productWithRelations->reviews ? $productWithRelations->reviews->count() : 0;
                $avgRating = $totalReviews > 0 ? $productWithRelations->reviews->avg('star') : null;

                $quantity = $product->quantity ?? 0;
                $unitsSold = $product->units_sold ?? 0;
                $leftStock = $quantity - $unitsSold;

                  // Then use safe access:
                  $sellingUnit = $product->sellingUnitAttribute?->attribute_value_unit ?? 'N/A';

                  // Or process the attribute value safely:
                  if ($productWithRelations->sellingUnitAttribute && $productWithRelations->sellingUnitAttribute->attribute_value) {
                    $fullValue = $productWithRelations->sellingUnitAttribute->attribute_value;
                    if (strpos($fullValue, '/') !== false) {
                        $parts = explode('/', $fullValue);
                        $sellingUnit = trim($parts[1]);
                    } else {
                        $sellingUnit = $fullValue;
                    }
                } else {
                    $sellingUnit = null;
                }
                

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'images' => $images,
                    'video_url' => $product->video_url,
                    'video_path' => $videos,
                    'sku' => $product->sku,
                    'original_price' => $product->price,
                    'front_sale_price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'price' => $product->price,
                    'start_date' => $product->start_date,
                    'end_date' => $product->end_date,
                    'warranty_information' => $product->warranty_information,
                    'currency' => $productWithRelations->currency?->title,
                    'total_reviews' => $totalReviews,
                    'avg_rating' => $avgRating,
                    'best_price' => $product->sale_price ?? $product->price,
                    'best_delivery_date' => null,
                    'leftStock' => $leftStock,
                    'currency_title' => $productWithRelations->currency
                        ? ($productWithRelations->currency->is_prefix_symbol
                            ? $productWithRelations->currency->title
                            : ($product->price . ' ' . $productWithRelations->currency->title))
                        : $product->price,
                    'selling_unit' => $sellingUnit   
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedProducts->values(),
                'pagination' => $pagination,
                'message' => 'Products retrieved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getProductsByBrandAndCategory: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

     protected function emptyPagination()
    {
        return [
            'total' => 0,
            'per_page' => 0,
            'current_page' => 1,
            'last_page' => 1,
        ];
    }

    protected function buildPagination($page, $perPage, $total)
    {
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }

    protected function normalizeMediaUrls($media)
    {
        if (is_array($media)) {
            return array_map(fn ($url) => url($url), $media);
        }
        return $media ? url($media) : null;
    }

    // public function getAllBrandsAlphabetically(): JsonResponse
    // {
    //     $brands = Brand::where('status', 'published')
    //         ->select('id', 'name', 'logo')
    //         ->orderBy('name') // ensures alphabetical order
    //         ->get()
    //         ->map(function ($brand) {
    //             $brand->logo = $brand->logo ? asset($brand->logo) : null;
    //             return $brand;
    //         });
    
    //     $grouped = $brands->groupBy(function ($brand) {
    //         return strtoupper(substr($brand->name, 0, 1)); // group by first letter
    //     })->sortKeys(); // sort alphabetically by keys (A, B, C...)
    
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Brands grouped alphabetically.',
    //         'data' => $grouped
    //     ]);
    // }
    
    public function getAllBrandsAlphabetically(Request $request): JsonResponse
    {
        $letter = strtoupper($request->query('letter')); // e.g. ?letter=B

        $brandsQuery = Brand::where('status', 'published')
        ->select('id', 'name', 'logo' , 'thumbnail' , 'ar_thumbnail' )
            ->orderBy('name');

        if ($letter) {
            $brandsQuery->where('name', 'LIKE', $letter . '%');
        }

        $brands = $brandsQuery->get()->map(function ($brand) {
            $brand->logo = $brand->logo ? asset($brand->logo) : null;
            $brand->thumbnail = $brand->thumbnail ? asset($brand->thumbnail) : null;
            $brand->ar_thumbnail = $brand->ar_thumbnail ? asset($brand->ar_thumbnail) : null;
            return $brand;
        });
    

        if ($letter) {
            // Return filtered brands only
            return response()->json([
                'success' => true,
                'message' => "Brands starting with letter '$letter'.",
                'data' => $brands
            ]);
        } else {
            // Return grouped by A-Z
            $grouped = $brands->groupBy(function ($brand) {
                return strtoupper(substr($brand->name, 0, 1));
            })->sortKeys();

            return response()->json([
                'success' => true,
                'message' => 'Brands grouped alphabetically.',
                'data' => $grouped
            ]);
        }
    }


    
}
