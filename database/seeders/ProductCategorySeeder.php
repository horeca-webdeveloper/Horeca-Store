<?php

namespace Database\Seeders;

use Botble\Base\Facades\MetaBox;
use Botble\Base\Supports\BaseSeeder;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Slug\Facades\SlugHelper;
use Botble\Slug\Models\Slug;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProductCategorySeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('product-categories');

        $categories = [
            [
                'name' => 'Hot Promotions',
                'icon' => 'icon-star',
            ],
            [
                'name' => 'Electronics',
                'icon' => 'icon-laundry',
                'image' => 'product-categories/1.jpg',
                'is_featured' => true,
                'children' => [
                    [
                        'name' => 'Consumer Electronic',
                        'children' => [
                            [
                                'name' => 'Home Audio & Theaters',
                            ],
                            [
                                'name' => 'TV & Videos',
                            ],
                            [
                                'name' => 'Camera, Photos & Videos',
                            ],
                            [
                                'name' => 'Cellphones & Accessories',
                            ],
                            [
                                'name' => 'Headphones',
                            ],
                            [
                                'name' => 'Videos games',
                            ],
                            [
                                'name' => 'Wireless Speakers',
                            ],
                            [
                                'name' => 'Office Electronic',
                            ],
                        ],
                    ],
                    [
                        'name' => 'Accessories & Parts',
                        'children' => [
                            [
                                'name' => 'Digital Cables',
                            ],
                            [
                                'name' => 'Audio & Video Cables',
                            ],
                            [
                                'name' => 'Batteries',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Clothing',
                'icon' => 'icon-shirt',
                'image' => 'product-categories/2.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Computers',
                'icon' => 'icon-desktop',
                'image' => 'product-categories/3.jpg',
                'is_featured' => true,
                'children' => [
                    [
                        'name' => 'Computer & Technologies',
                        'children' => [
                            [
                                'name' => 'Computer & Tablets',
                            ],
                            [
                                'name' => 'Laptop',
                            ],
                            [
                                'name' => 'Monitors',
                            ],
                            [
                                'name' => 'Computer Components',
                            ],
                        ],
                    ],
                    [
                        'name' => 'Networking',
                        'children' => [
                            [
                                'name' => 'Drive & Storages',
                            ],
                            [
                                'name' => 'Gaming Laptop',
                            ],
                            [
                                'name' => 'Security & Protection',
                            ],
                            [
                                'name' => 'Accessories',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Home & Kitchen',
                'icon' => 'icon-lampshade',
                'image' => 'product-categories/4.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Health & Beauty',
                'icon' => 'icon-heart-pulse',
                'image' => 'product-categories/5.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Jewelry & Watch',
                'icon' => 'icon-diamond2',
                'image' => 'product-categories/6.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Technology Toys',
                'icon' => 'icon-desktop',
                'image' => 'product-categories/7.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Phones',
                'icon' => 'icon-smartphone',
                'image' => 'product-categories/8.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Babies & Moms',
                'icon' => 'icon-baby-bottle',
            ],
            [
                'name' => 'Sport & Outdoor',
                'icon' => 'icon-baseball',
            ],
            [
                'name' => 'Books & Office',
                'icon' => 'icon-book2',
            ],
            [
                'name' => 'Cars & Motorcycles',
                'icon' => 'icon-car-siren',
            ],
            [
                'name' => 'Home Improvements',
                'icon' => 'icon-wrench',
            ],
        ];

        ProductCategory::query()->truncate();

        foreach ($categories as $index => $item) {
            $this->createCategoryItem($index, $item);
        }
    }

    protected function createCategoryItem(int $index, array $category, int|string $parentId = 0): void
    {
        $category['parent_id'] = $parentId;
        $category['order'] = $index;

        if (Arr::has($category, 'children')) {
            $children = $category['children'];
            unset($category['children']);
        } else {
            $children = [];
        }

        $createdCategory = ProductCategory::query()->create(Arr::except($category, ['icon']));

        Slug::query()->create([
            'reference_type' => ProductCategory::class,
            'reference_id' => $createdCategory->id,
            'key' => Str::slug($createdCategory->name),
            'prefix' => SlugHelper::getPrefix(ProductCategory::class),
        ]);

        if (isset($category['icon'])) {
            MetaBox::saveMetaBoxData($createdCategory, 'icon', $category['icon']);
        }

        if ($children) {
            foreach ($children as $childIndex => $child) {
                $this->createCategoryItem($childIndex, $child, $createdCategory->id);
            }
        }
    }
}
