<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Ecommerce\Models\Brand;
use Botble\Slug\Facades\SlugHelper;
use Botble\Slug\Models\Slug;
use Illuminate\Support\Str;

class BrandSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('brands');

        $brands = [
            [
                'name' => 'Fashion live',
            ],
            [
                'name' => 'Hand crafted',
            ],
            [
                'name' => 'Mestonix',
            ],
            [
                'name' => 'Sunshine',
            ],
            [
                'name' => 'Pure',
            ],
            [
                'name' => 'Anfold',
            ],
            [
                'name' => 'Automotive',
            ],
        ];

        Brand::query()->truncate();

        foreach ($brands as $key => $item) {
            $item['order'] = $key;
            $item['is_featured'] = true;
            $item['logo'] = 'brands/' . ($key + 1) . '.jpg';
            $brand = Brand::query()->create($item);

            Slug::query()->create([
                'reference_type' => Brand::class,
                'reference_id' => $brand->id,
                'key' => Str::slug($brand->name),
                'prefix' => SlugHelper::getPrefix(Brand::class),
            ]);
        }
    }
}
