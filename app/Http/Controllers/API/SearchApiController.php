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

    public function search(Request $request)
    {
        $query = $request->input('query');
    
        if (empty($query)) {
            // Default response with random items
            $products = Product::inRandomOrder()->take(4)->with('slugable')->get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $this->getFullImageUrl($product->image),
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'slug' => optional($product->slugable)->key, // Fetch slug from morph relation
                ];
            });
    
            // $categories = Productcategory::inRandomOrder()->take(4)->with('slugable')->get()->map(function ($category) {
            //     return [
            //         'id' => $category->id,
            //         'name' => $category->name,
            //         'slug' => optional($category->slugable)->key, // Fetch slug from morph relation
            //         'products' => $category->products()->inRandomOrder()->take(3)->with('slugable')->get()->map(function ($product) {
            //             return [
            //                 'id' => $product->id,
            //                 'name' => $product->name,
            //                 'slug' => optional($product->slugable)->key,
            //                 'image' => $this->getFullImageUrl($product->image),
            //                 'price' => $product->price,
            //                 'sale_price' => $product->sale_price,
            //             ];
            //         }),
            //     ];
            // });

            // In the categories part of both conditions (empty query and search), update the mapping:

                $categories = Productcategory::where('name', 'LIKE', "%{$query}%")
                ->orWhereHas('slugable', function ($q) use ($query) {
                    $q->where('key', 'LIKE', "%{$query}%");
                })
                ->take(4)
                ->with('slugable')
                ->get()
                ->map(function ($category) {
                    $slugPath = [];
                    $current = $category;
                    
                    // Get parent slugs
                    while ($current->parent_id) {
                        $parent = Productcategory::with('slugable')
                            ->find($current->parent_id);
                        
                        if ($parent && $parent->slugable) {
                            array_unshift($slugPath, $parent->slugable->key);
                        }
                        $current = $parent;
                    }
                    
                    // Add current category's slug
                    if ($category->slugable) {
                        $slugPath[] = $category->slugable->key;
                    }

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => optional($category->slugable)->key,
                        'slug_path' => implode('/', $slugPath),
                        'products' => $category->products()->take(3)->with('slugable')->get()->map(function ($product) {
                            return [
                                'id' => $product->id,
                                'name' => $product->name,
                                'slug' => optional($product->slugable)->key,
                                'image' => $this->getFullImageUrl($product->image),
                                'price' => $product->price,
                                'sale_price' => $product->sale_price,
                            ];
                        }),
                    ];
                });
    
            $brands = Brand::inRandomOrder()->take(4)->with('slugable')->get()->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => optional($brand->slugable)->key, // Fetch slug from morph relation

                    'products' => $brand->products()->inRandomOrder()->take(3)->with('slugable')->get()->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => optional($product->slugable)->key,
                            'image' => $this->getFullImageUrl($product->image),
                            'price' => $product->price,
                            'sale_price' => $product->sale_price,
                        ];
                    }),
                ];
            });
        } else {
            // Search functionality
            $products = Product::where('name', 'LIKE', "%{$query}%")
                ->orWhereHas('slugable', function ($q) use ($query) {
                    $q->where('key', 'LIKE', "%{$query}%");
                })
                ->take(4)
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
    
            // $categories = Productcategory::where('name', 'LIKE', "%{$query}%")
            //     ->orWhereHas('slugable', function ($q) use ($query) {
            //         $q->where('key', 'LIKE', "%{$query}%");
            //     })
            //     ->take(4)
            //     ->with('slugable')
            //     ->get()
            //     ->map(function ($category) {
            //         return [
            //             'id' => $category->id,
            //             'name' => $category->name,
            //             'products' => $category->products()->take(3)->with('slugable')->get()->map(function ($product) {
            //                 return [
            //                     'id' => $product->id,
            //                     'name' => $product->name,
            //                     'slug' => optional($product->slugable)->key,
            //                     'image' => $this->getFullImageUrl($product->image),
            //                     'price' => $product->price,
            //                     'sale_price' => $product->sale_price,
            //                 ];
            //             }),
            //         ];
            //     });

            $categories = Productcategory::inRandomOrder()->take(4)->with('slugable')->get()->map(function ($category) {
                $slugPath = [];
                $current = $category;
                
                // Get parent slugs
                while ($current->parent_id) {
                    $parent = Productcategory::with('slugable')
                        ->find($current->parent_id);
                    
                    if ($parent && $parent->slugable) {
                        array_unshift($slugPath, $parent->slugable->key);
                    }
                    $current = $parent;
                }
                
                // Add current category's slug
                if ($category->slugable) {
                    $slugPath[] = $category->slugable->key;
                }
            
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => optional($category->slugable)->key,
                    'slug_path' => implode('/', $slugPath),
                    'products' => $category->products()->inRandomOrder()->take(3)->with('slugable')->get()->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => optional($product->slugable)->key,
                            'image' => $this->getFullImageUrl($product->image),
                            'price' => $product->price,
                            'sale_price' => $product->sale_price,
                        ];
                    }),
                ];
            });
    
            $brands = Brand::where('name', 'LIKE', "%{$query}%")
                ->orWhereHas('slugable', function ($q) use ($query) {
                    $q->where('key', 'LIKE', "%{$query}%");
                })
                ->take(4)
                ->with('slugable')
                ->get()
                ->map(function ($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'products' => $brand->products()->take(3)->with('slugable')->get()->map(function ($product) {
                            return [
                                'id' => $product->id,
                                'name' => $product->name,
                                'slug' => optional($product->slugable)->key,
                                'image' => $this->getFullImageUrl($product->image),
                                'price' => $product->price,
                                'sale_price' => $product->sale_price,
                            ];
                        }),
                    ];
                });
        }
    
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

    //     $categories = Productcategory::where('name', 'LIKE', "%{$query}%")
    //         ->orWhereHas('slugable', function ($q) use ($query) {
    //             $q->where('key', 'LIKE', "%{$query}%");
    //         })
    //         ->take(10)
    //         ->with('slugable')
    //         ->get()
    //         ->map(function ($category) {
    //             return [
    //                 'id' => $category->id,
    //                 'name' => $category->name,
    //                 'slug' => optional($category->slugable)->key,
    //             ];
    //         });

    //     return response()->json(['categories' => $categories]);
    // }
    public function searchCategories(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query)) {
            return response()->json(['categories' => []]);
        }
    
        $categories = Productcategory::where('name', 'LIKE', "%{$query}%")
            ->orWhereHas('slugable', function($q) use ($query) {
                $q->where('key', 'LIKE', "%{$query}%");
            })
            ->with('slugable')  // Eager load slugs
            ->take(10)
            ->get()
            ->map(function ($category) {
                $slugPath = [];
                $current = $category;
                
                // Get parent slugs
                while ($current->parent_id) {
                    $parent = Productcategory::with('slugable')
                        ->find($current->parent_id);
                    
                    if ($parent && $parent->slugable) {
                        array_unshift($slugPath, $parent->slugable->key);
                    }
                    $current = $parent;
                }
                
                // Add current category's slug
                if ($category->slugable) {
                    $slugPath[] = $category->slugable->key;
                }
    
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => optional($category->slugable)->key,
                    'slug_path' => implode('/', $slugPath)
                ];
            });
    
        return response()->json(['categories' => $categories]);
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

