<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Product;
use Botble\Marketplace\Models\Store;
use Botble\Marketplace\Models\VendorInfo;
use Botble\Slug\Facades\SlugHelper;
use Botble\Slug\Models\Slug;
use Faker\Provider\en_US\Address as FakerAddress;
use Illuminate\Support\Str;

class MarketplaceSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('stores');

        Customer::query()->where('is_vendor', 1)->update(['is_vendor' => 0]);
        Store::query()->truncate();
        VendorInfo::query()->truncate();
        Slug::query()->where('reference_type', Store::class)->delete();

        $faker = fake();

        $vendors = [];
        foreach (Customer::query()->get() as $customer) {
            $customer->is_vendor = $customer->id == 1 ? 0 : ($customer->id == 2 ? 1 : rand(0, 1));
            $customer->vendor_verified_at = $customer->is_vendor ? now() : null;
            $customer->save();

            if ($customer->is_vendor) {
                $vendors[] = $customer->id;

                $vendorInfo = new VendorInfo();
                $vendorInfo->bank_info = [
                    'name' => $faker->name(),
                    'number' => $faker->e164PhoneNumber(),
                    'full_name' => $faker->name(),
                    'description' => $faker->name(),
                ];
                $vendorInfo->customer_id = $customer->id;

                $vendorInfo->save();
            }
        }

        $storeNames = [
            'GoPro',
            'Global Office',
            'Young Shop',
            'Global Store',
            'Robert’s Store',
            'Stouffer',
            'StarKist',
            'Old El Paso',
            'Tyson',
        ];

        for ($i = 0; $i < count($vendors); $i++) {
            $store = Store::query()->create([
                'name' => $storeNames[$i],
                'email' => $faker->safeEmail(),
                'phone' => $faker->e164PhoneNumber(),
                'logo' => 'stores/' . ($i + 1) . '.png',
                'country' => $faker->countryCode(),
                'state' => FakerAddress::state(),
                'city' => $faker->city(),
                'address' => $faker->streetAddress(),
                'customer_id' => $vendors[$i],
                'description' => $faker->text(50),
                'content' => $faker->text(150),
            ]);

            Slug::query()->create([
                'reference_type' => Store::class,
                'reference_id' => $store->id,
                'key' => Str::slug($store->name),
                'prefix' => SlugHelper::getPrefix(Store::class),
            ]);
        }

        foreach (Product::query()->where('is_variation', 0)->get() as $product) {
            $product->store_id = Store::query()->inRandomOrder()->value('id');
            $product->save();
        }
    }
}
