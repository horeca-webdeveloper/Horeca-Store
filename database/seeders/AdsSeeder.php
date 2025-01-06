<?php

namespace Database\Seeders;

use Botble\Ads\Models\Ads;
use Botble\Base\Supports\BaseSeeder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AdsSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('promotion');

        Ads::query()->truncate();

        $items = [
            [
                'name' => 'Top Slider Image 1',
                'location' => 'not_set',
                'key' => 'VC2C8Q1UGCBG',
            ],
            [
                'name' => 'Top Slider Image 2',
                'location' => 'not_set',
                'key' => 'NBDWRXTSVZ8N',
            ],
            [
                'name' => 'Homepage middle 1',
                'location' => 'not_set',
                'key' => 'IZ6WU8KUALYD',
            ],
            [
                'name' => 'Homepage middle 2',
                'location' => 'not_set',
                'key' => 'ILSFJVYFGCPZ',
            ],
            [
                'name' => 'Homepage middle 3',
                'location' => 'not_set',
                'key' => 'ZDOZUZZIU7FT',
            ],
            [
                'name' => 'Homepage big 1',
                'location' => 'not_set',
                'key' => 'Q9YDUIC9HSWS',
            ],
            [
                'name' => 'Homepage bottom small',
                'location' => 'not_set',
            ],
            [
                'name' => 'Product sidebar',
                'location' => 'product-sidebar',
            ],
            [
                'name' => 'Homepage big 2',
                'location' => 'not_set',
                'key' => 'IZ6WU8KUALYE',
            ],
        ];

        foreach ($items as $index => $item) {
            $item['order'] = $index + 1;
            if (! isset($item['key'])) {
                $item['key'] = strtoupper(Str::random(12));
            }
            $item['expired_at'] = Carbon::now()->addYears(5)->toDateString();
            $item['image'] = 'promotion/' . ($index + 1) . '.jpg';
            $item['url'] = '/products';

            Ads::query()->create($item);
        }
    }
}
