<?php

// namespace App\Http\Controllers\API;

// use Illuminate\Http\Request;
// use App\Http\Controllers\Controller; // Import the Controller class
// use Botble\Ecommerce\Models\Product;
// use Botble\Ecommerce\Models\Brand;
// use Botble\Ecommerce\Models\Productcategory;

// class SearchApiController extends Controller
// {
//     public function search(Request $request)
//     {
//         // Get the search term from the request
//         $query = $request->input('query');

//         if (empty($query)) {
//             return response()->json([
//                 'error' => 'Query parameter is required.'
//             ], 400);
//         }

//         // Search in `ec_products` table for name or SKU, limit to 5 results
//         $products = Product::where('name', 'LIKE', "%{$query}%")
//             ->orWhere('sku', 'LIKE', "%{$query}%")
//             ->take(5)
//             ->get();

//         // Search in `ec_brands` table for name, limit to 5 results
//         $brands = Brand::where('name', 'LIKE', "%{$query}%")
//             ->take(5)
//             ->get();

//         // Search in `ec_product_categories` table for name, limit to 5 results
//         $categories = Productcategory::where('name', 'LIKE', "%{$query}%")
//             ->take(5)
//             ->get();

//         // Combine the results
//         $results = [
//             'products' => $products,
//             'brands' => $brands,
//             'categories' => $categories,
//         ];

//         return response()->json($results);
//     }
// }



// namespace App\Http\Controllers\API;

// use Illuminate\Http\Request;
// use App\Http\Controllers\Controller; // Import the Controller class
// use Botble\Ecommerce\Models\Product;
// use Botble\Ecommerce\Models\Brand;
// use Botble\Ecommerce\Models\Productcategory;

// class SearchApiController extends Controller
// {
//     public function search(Request $request)
//     {
//         // Get the search term from the request
//         $query = $request->input('query');

//         if (empty($query)) {
//             // If the query is empty, return random products, brands, and categories
//             $products = Product::inRandomOrder()->take(5)->get();
//             $brands = Brand::inRandomOrder()->take(5)->get();
//             $categories = Productcategory::inRandomOrder()->take(5)->get();
//         } else {
//             // Search in `ec_products` table for name or SKU, limit to 5 results
//             $products = Product::where('name', 'LIKE', "%{$query}%")
//                 ->orWhere('sku', 'LIKE', "%{$query}%")
//                 ->take(5)
//                 ->get();

//             // Search in `ec_brands` table for name, limit to 5 results
//             $brands = Brand::where('name', 'LIKE', "%{$query}%")
//                 ->take(5)
//                 ->get();

//             // Search in `ec_product_categories` table for name, limit to 5 results
//             $categories = Productcategory::where('name', 'LIKE', "%{$query}%")
//                 ->take(5)
//                 ->get();
//         }

//         // Combine the results
//         $results = [
//             'products' => $products,
//             'brands' => $brands,
//             'categories' => $categories,
//         ];

//         return response()->json($results);
//     }
// }


// namespace App\Http\Controllers\API;

// use Illuminate\Http\Request;
// use App\Http\Controllers\Controller;
// use Botble\Ecommerce\Models\Product;
// use Botble\Ecommerce\Models\Brand;
// use Botble\Ecommerce\Models\Productcategory;

// class SearchApiController extends Controller
// {
//     public function search(Request $request)
//     {
//         // Get the search term from the request
//         $query = $request->input('query');

//         if (empty($query)) {
//             // If the query is empty, return random products, brands, and categories
//             $products = Product::inRandomOrder()->take(5)->get();
//             $brands = Brand::inRandomOrder()->take(5)->get();
//             $categories = Productcategory::inRandomOrder()->take(5)->get();
//         } else {
//             // Search in `ec_products` table for name or SKU, limit to 5 results
//             $products = Product::where('name', 'LIKE', "%{$query}%")
//                 ->orWhere('sku', 'LIKE', "%{$query}%")
//                 ->take(5)
//                 ->get();

//             // Search in `ec_brands` table for name, limit to 5 results
//             $brands = Brand::where('name', 'LIKE', "%{$query}%")
//                 ->take(5)
//                 ->get();

//             // Search in `ec_product_categories` table for name, limit to 5 results
//             $categories = Productcategory::where('name', 'LIKE', "%{$query}%")
//                 ->take(5)
//                 ->get();
//         }

//         // Modify the product images to return only the image name
//         $products = $products->map(function ($product) {
//             $product->image = basename($product->image); // Extract the image name from the path
//             return $product;
//         });

//         // Combine the results
//         $results = [
//             'products' => $products,
//             'brands' => $brands,
//             'categories' => $categories,
//         ];

//         return response()->json($results);
//     }
// }


namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Brand;
use Botble\Ecommerce\Models\Productcategory;
use RvMedia;
use Illuminate\Support\Facades\Cache;
class SearchApiController extends Controller
{
    // public function search(Request $request)
    // {
    //     $query = $request->input('query');

    //     if (empty($query)) {
    //         // If the query is empty, return random products, brands, and categories
    //         $products = Product::inRandomOrder()->take(5)->get()->map(function ($product) {
    //             return [
    //                 'id' => $product->id,
    //                 'name' => $product->name,
    //                 'sku' => $product->sku,
    //                 'image' => $this->getFullImageUrl($product->image),
    //             ];
    //         });

    //         $brands = Brand::inRandomOrder()->take(5)->get()->map(function ($brand) {
    //             return [
    //                 'id' => $brand->id,
    //                 'name' => $brand->name,
    //                 'logo' => $this->getFullImageUrl($brand->logo),
    //             ];
    //         });

    //         $categories = Productcategory::inRandomOrder()->take(5)->get()->map(function ($category) {
    //             return [
    //                 'id' => $category->id,
    //                 'name' => $category->name,
    //                 'image' => $this->getFullImageUrl($category->image),
    //             ];
    //         });
    //     } else {
    //         // If a query is provided, search in the respective tables
    //         $products = Product::where('name', 'LIKE', "%{$query}%")
    //             ->orWhere('sku', 'LIKE', "%{$query}%")
    //             ->take(5)
    //             ->get()
    //             ->map(function ($product) {
    //                 return [
    //                     'id' => $product->id,
    //                     'name' => $product->name,
    //                     'sku' => $product->sku,
    //                     'image' => $this->getFullImageUrl($product->image),
    //                 ];
    //             });

    //         $brands = Brand::where('name', 'LIKE', "%{$query}%")
    //             ->take(5)
    //             ->get()
    //             ->map(function ($brand) {
    //                 return [
    //                     'id' => $brand->id,
    //                     'name' => $brand->name,
    //                     'logo' => $this->getFullImageUrl($brand->logo),
    //                 ];
    //             });

    //         $categories = Productcategory::where('name', 'LIKE', "%{$query}%")
    //             ->take(5)
    //             ->get()
    //             ->map(function ($category) {
    //                 return [
    //                     'id' => $category->id,
    //                     'name' => $category->name,
    //                     'image' => $this->getFullImageUrl($category->image),
    //                 ];
    //             });
    //     }

    //     // Combine the results
    //     $results = [
    //         'products' => $products,
    //         'brands' => $brands,
    //         'categories' => $categories,
    //     ];

    //     return response()->json($results);
    // }

    // public function search(Request $request)
    // {
    //     $query = $request->input('query');
    
    //     if (empty($query)) {
    //         $products = Product::where('status', 'published')
    //             ->inRandomOrder()
    //             ->take(4)
    //             ->with('slugable')
    //             ->get()
    //             ->map(function ($product) {
    //                 return [
    //                     'id' => $product->id,
    //                     'name' => $product->name,
    //                     'url' => $product->url,
    //                     'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
    //                 ];
    //             });
    
    //            // Fetch categories with associated products
    //             $categories = Productcategory::with(['products' => function($query) {
    //                 $query->where('status', 'published')->take(3); // Only published products
    //             }])->inRandomOrder()->take(4)->get();

    //             // Fetch brands with associated products
    //             $brands = Brand::with(['products' => function($query) {
    //                 $query->where('status', 'published')->take(3); // Only published products
    //             }])->inRandomOrder()->take(4)->get();

    //         // Mapping the data for output
    //         $categories = $categories->map(function ($category) {
    //             return [
    //                 'id' => $category->id,
    //                 'name' => $category->name,
    //                 'slug' => optional($category->slugable)->key,
    //                 'url' => $category->url,
    //                 'image' => RvMedia::getImageUrl($category->image, 'thumb', false, RvMedia::getDefaultImage()),
    //                 'products' => $category->products->map(function ($product) {
    //                     return [
    //                         'id' => $product->id,
    //                         'name' => $product->name,
    //                         'slug' => optional($product->slugable)->key,
    //                         'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
    //                         'price' => $product->price,
    //                         'sale_price' => $product->sale_price,
    //                     ];
    //                 }),
    //             ];
    //         });

    //         $brands = $brands->map(function ($brand) {
    //             return [
    //                 'id' => $brand->id,
    //                 'name' => $brand->name,
    //                 'url' => $brand->url,
    //                 'slug' => optional($brand->slugable)->key,
    //                 'image' => RvMedia::getImageUrl($brand->logo, 'thumb', false, RvMedia::getDefaultImage()),
    //                 'products' => $brand->products->map(function ($product) {
    //                     return [
    //                         'id' => $product->id,
    //                         'name' => $product->name,
    //                         'slug' => optional($product->slugable)->key,
    //                         'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
    //                         'price' => $product->price,
    //                         'sale_price' => $product->sale_price,
    //                     ];
    //                 }),
    //             ];
    //         });

            
    
    //         return response()->json([
    //             'products' => $products,
    //             'categories' => $categories,
    //             'brands' => $brands,
    //         ]);
    //     }
    
    //     $products = Product::where('status', 'published')
    //     ->where(function ($q) use ($query) {
    //         $q->where('name', 'LIKE', "{$query}%")
    //           ->orWhere('name', 'LIKE', "%{$query}%")
    //           ->orWhere('sku', 'LIKE', "{$query}%")
    //           ->orWhere('sku', 'LIKE', "%{$query}%")
    //           ->orWhereHas('slugable', function ($q) use ($query) {
    //               $q->where('key', 'LIKE', "{$query}%")
    //                 ->orWhere('key', 'LIKE', "%{$query}%");
    //           });
    //     })
    //     ->take(5)
    //     ->with('slugable') // ✅ fixed
    //     ->get()
    //     ->map(function ($product) {
    //         return [
    //             'id' => $product->id,
    //             'name' => $product->name,
    //             'image' => $this->getFullImageUrl($product->image),
    //             'slug' => optional($product->slugable)->key,
    //             'price' => $product->price,
    //             'sale_price' => $product->sale_price,
    //         ];
    //     });
    

    
    //     $categories = Productcategory::where(function ($q) use ($query) {
    //             $q->where('name', 'LIKE', "{$query}%")
    //               ->orWhere('name', 'LIKE', "%{$query}%")
    //               ->orWhereHas('slugable', function ($q) use ($query) {
    //                   $q->where('key', 'LIKE', "{$query}%")
    //                     ->orWhere('key', 'LIKE', "%{$query}%");
    //               });
    //         })
    //         ->take(5)
    //         ->with('slugable')
    //         ->get()
    //         ->map(function ($category) {
    //             return [
    //                 'id' => $category->id,
    //                 'name' => $category->name,
    //                 'slug' => optional($category->slugable)->key,
    //                 'url' => $category->url,
    //                 'image' => RvMedia::getImageUrl($category->image, 'thumb', false, RvMedia::getDefaultImage()),
    //                 'products' => $category->products->map(function ($product) {
    //                     return [
    //                         'id' => $product->id,
    //                         'name' => $product->name,
    //                         'slug' => optional($product->slugable)->key,
    //                         'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
    //                         'price' => $product->price,
    //                         'sale_price' => $product->sale_price,
    //                     ];
    //                 }),
    //             ];
    //         });
    
    //     $brands = Brand::where(function ($q) use ($query) {
    //             $q->where('name', 'LIKE', "{$query}%")
    //               ->orWhere('name', 'LIKE', "%{$query}%")
    //               ->orWhereHas('slugable', function ($q) use ($query) {
    //                   $q->where('key', 'LIKE', "{$query}%")
    //                     ->orWhere('key', 'LIKE', "%{$query}%");
    //               });
    //         })
    //         ->take(5)
    //         ->with('slugable')
    //         ->get()
    //         ->map(function ($brand) {
    //             return [
    //                 'id' => $brand->id,
    //                 'name' => $brand->name,
    //                 'slug' => optional($brand->slugable)->key,
    //                 'url' => $brand->url,
    //                 'image' => RvMedia::getImageUrl($brand->logo, 'thumb', false, RvMedia::getDefaultImage()),
    //                 'products' => $brand->products->map(function ($product) {
    //                     return [
    //                         'id' => $product->id,
    //                         'name' => $product->name,
    //                         'slug' => optional($product->slugable)->key,
    //                         'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
    //                         'price' => $product->price,
    //                         'sale_price' => $product->sale_price,
    //                     ];
    //                 }),
    //             ];
    //         });
    
    //     return response()->json([
    //         'products' => $products,
    //         'categories' => $categories,
    //         'brands' => $brands,
    //     ]);
    // }
    public function search(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            $products = Product::where('status', 'published')
                ->inRandomOrder()
                ->take(4)
                ->with('slugable')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'url' => $product->url,
                        'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
                    ];
                });

            // Fetch categories with associated products
                $categories = Productcategory::with(['products' => function($query) {
                    $query->where('status', 'published')->take(3); // Only published products
                }])->inRandomOrder()->take(4)->get();

                // Fetch brands with associated products
                $brands = Brand::with(['products' => function($query) {
                    $query->where('status', 'published')->take(3); // Only published products
                }])->inRandomOrder()->take(4)->get();

            // Mapping the data for output
            $categories = $categories->map(function ($category) {
                // Get parent category if exists
                $parentCategory = null;
                $parentSlug = null;
                $parentId = null;
                $parentParentSlug = null;

                if ($category->parent_id) {
                    $parentCategory = Productcategory::with('slugable')->find($category->parent_id);
                    if ($parentCategory) {
                        $parentSlug = optional($parentCategory->slugable)->key;
                        $parentId = $parentCategory->id;
                        
                        // Get grandparent if exists
                        if ($parentCategory->parent_id) {
                            $grandparentCategory = Productcategory::with('slugable')->find($parentCategory->parent_id);
                            if ($grandparentCategory) {
                                $parentParentSlug = optional($grandparentCategory->slugable)->key;
                            }
                        }
                    }
                }

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => optional($category->slugable)->key,
                    'url' => $category->url,
                    'image' => RvMedia::getImageUrl($category->image, 'thumb', false, RvMedia::getDefaultImage()),
                    'parent_id' => $category->parent_id,
                    'parent_slug' => $parentSlug,
                    'parent_parent_slug' => $parentParentSlug,
                    'products' => $category->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => optional($product->slugable)->key,
                            'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
                            'price' => $product->price,
                            'sale_price' => $product->sale_price,
                        ];
                    }),
                ];
            });

            $brands = $brands->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'url' => $brand->url,
                    'slug' => optional($brand->slugable)->key,
                    'image' => RvMedia::getImageUrl($brand->logo, 'thumb', false, RvMedia::getDefaultImage()),
                    'products' => $brand->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => optional($product->slugable)->key,
                            'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
                            'price' => $product->price,
                            'sale_price' => $product->sale_price,
                        ];
                    }),
                ];
            });

            
            return response()->json([
                'products' => $products,
                'categories' => $categories,
                'brands' => $brands,
            ]);
        }

        $products = Product::where('status', 'published')
        ->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->orWhere('sku', 'LIKE', "{$query}%")
            ->orWhere('sku', 'LIKE', "%{$query}%")
            ->orWhereHas('slugable', function ($q) use ($query) {
                $q->where('key', 'LIKE', "{$query}%")
                    ->orWhere('key', 'LIKE', "%{$query}%");
            });
        })
        ->take(5)
        ->with('slugable') 
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'image' => $this->getFullImageUrl($product->image),
                'slug' => optional($product->slugable)->key,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
            ];
        });


        $categories = Productcategory::where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "{$query}%")
                ->orWhere('name', 'LIKE', "%{$query}%")
                ->orWhereHas('slugable', function ($q) use ($query) {
                    $q->where('key', 'LIKE', "{$query}%")
                        ->orWhere('key', 'LIKE', "%{$query}%");
                });
            })
            ->take(5)
            ->with('slugable')
            ->get()
            ->map(function ($category) {
                // Get parent category if exists
                $parentCategory = null;
                $parentSlug = null;
                $parentId = null;
                $parentParentSlug = null;

                if ($category->parent_id) {
                    $parentCategory = Productcategory::with('slugable')->find($category->parent_id);
                    if ($parentCategory) {
                        $parentSlug = optional($parentCategory->slugable)->key;
                        $parentId = $parentCategory->id;
                        
                        // Get grandparent if exists
                        if ($parentCategory->parent_id) {
                            $grandparentCategory = Productcategory::with('slugable')->find($parentCategory->parent_id);
                            if ($grandparentCategory) {
                                $parentParentSlug = optional($grandparentCategory->slugable)->key;
                            }
                        }
                    }
                }

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => optional($category->slugable)->key,
                    'url' => $category->url,
                    'image' => RvMedia::getImageUrl($category->image, 'thumb', false, RvMedia::getDefaultImage()),
                    'parent_id' => $category->parent_id,
                    'parent_slug' => $parentSlug,
                    'parent_parent_slug' => $parentParentSlug,
                    'products' => $category->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => optional($product->slugable)->key,
                            'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
                            'price' => $product->price,
                            'sale_price' => $product->sale_price,
                        ];
                    }),
                ];
            });

        $brands = Brand::where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "{$query}%")
                ->orWhere('name', 'LIKE', "%{$query}%")
                ->orWhereHas('slugable', function ($q) use ($query) {
                    $q->where('key', 'LIKE', "{$query}%")
                        ->orWhere('key', 'LIKE', "%{$query}%");
                });
            })
            ->take(5)
            ->with('slugable')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => optional($brand->slugable)->key,
                    'url' => $brand->url,
                    'image' => RvMedia::getImageUrl($brand->logo, 'thumb', false, RvMedia::getDefaultImage()),
                    'products' => $brand->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => optional($product->slugable)->key,
                            'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
                            'price' => $product->price,
                            'sale_price' => $product->sale_price,
                        ];
                    }),
                ];
            });

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }
  



    // public function searchCategories(Request $request)
    // {
    //     $query = $request->input('query');
        
    //     if (empty($query)) {
    //         return response()->json(['categories' => []]);
    //     }
    
    //     // Use a cache key based on the query, so we cache results for the specific query
    //     $cacheKey = 'categories_search_' . md5($query);
        
    //     // Check if the result is cached
    //     $categories = Cache::get($cacheKey);
    
    //     // If not cached, query the database and cache the result
    //     if (!$categories) {
    //         // Query to get categories with their slugs and parent category slugs
    //         $categories = Productcategory::where('name', 'LIKE', "%{$query}%")
    //             ->orWhereHas('slugable', function($q) use ($query) {
    //                 $q->where('key', 'LIKE', "%{$query}%");
    //             })
    //             ->with(['slugable', 'parentCategory.slugable'])  // Eager load slugs and parent slugs
    //             ->take(10)
    //             ->get()
    //             ->map(function ($category) {
    //                 return [
    //                     'id' => $category->id,
    //                     'name' => $category->name,
    //                     'slug' => optional($category->slugable)->key,
    //                     'slug_path' => $this->getSlugPath($category),
    //                 ];
    //             });
    
    //         // Cache the result for 60 minutes (you can adjust this time)
    //         Cache::put($cacheKey, $categories, 60); // Cache for 60 minutes
    //     }
    
    //     return response()->json(['categories' => $categories]);
    // }
    
    public function searchCategories(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json(['categories' => []]);
        }

        $cacheKey = 'categories_search_' . md5($query);

        $categories = Cache::get($cacheKey);

        if (!$categories) {
            $categories = ProductCategory::where('status', 'published') // Filter only published categories
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhereHas('slugable', function ($subQ) use ($query) {
                        $subQ->where('key', 'LIKE', "%{$query}%");
                    });
                })
                ->with(['slugable', 'parentCategory.slugable'])
                ->take(10)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => optional($category->slugable)->key,
                        'slug_path' => $this->getSlugPath($category),
                    ];
                });

            Cache::put($cacheKey, $categories, 60);
        }

        return response()->json(['categories' => $categories]);
    }

    public function getSlugPath($category)
    {
        $slugPath = [];
        $current = $category;
    
        // Collect parent categories slugs efficiently
        while ($current->parent_id) {
            $parent = $current->parentCategory; // Lazy load parent category
            if ($parent && $parent->slugable) {
                array_unshift($slugPath, $parent->slugable->key);
            }
            $current = $parent;
        }
    
        // Add the current category's slug
        if ($category->slugable) {
            $slugPath[] = $category->slugable->key;
        }
    
        return implode('/', $slugPath);
    }


    // private function getFullImageUrl($imagePath)
    // {
    //     if (!$imagePath) {
    //         return null; // Handle null cases
    //     }

    //     // Check if the image path is already a full URL
    //     if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
    //         return $imagePath; // Return as is
    //     }

    //     // Otherwise, append the base storage path
    //     if (str_starts_with($imagePath, 'products/')) {
    //         return asset('storage/' . $imagePath);
    //     }

    //     return asset('storage/' . $imagePath);
    // }
    private function getFullImageUrl($imagePath)
{
    if (!$imagePath) {
        return null; // Handle null cases
    }

    // Check if the image path starts with http or https, return it as is
    if (preg_match('/^https?:\/\//', $imagePath)) {
        return $imagePath;
    }

    // Use RvMedia to get the full image URL
    return RvMedia::getImageUrl($imagePath);
}
}

