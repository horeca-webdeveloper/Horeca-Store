<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Ecommerce\Models\Address;
use Botble\Ecommerce\Models\Customer;
use Faker\Provider\en_US\Address as FakerAddress;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('customers');

        Customer::query()->truncate();
        Address::query()->truncate();

        $customers = [
            'customer@botble.com',
            'vendor@botble.com',
        ];

        foreach ($customers as $item) {
            $customer = Customer::query()->create([
                'name' => fake()->name(),
                'email' => $item,
                'password' => Hash::make('12345678'),
                'phone' => fake()->e164PhoneNumber(),
                'avatar' => 'customers/' . fake()->numberBetween(1, 10) . '.jpg',
                'dob' => now()->subYears(rand(20, 50))->subDays(rand(1, 30)),
            ]);

            $customer->confirmed_at = now();
            $customer->save();

            Address::query()->create([
                'name' => $customer->name,
                'phone' => fake()->e164PhoneNumber(),
                'email' => $customer->email,
                'country' => fake()->countryCode(),
                'state' => FakerAddress::state(),
                'city' => fake()->city(),
                'address' => fake()->streetAddress(),
                'zip_code' => fake()->postcode(),
                'customer_id' => $customer->id,
                'is_default' => true,
            ]);

            Address::query()->create([
                'name' => $customer->name,
                'phone' => fake()->e164PhoneNumber(),
                'email' => $customer->email,
                'country' => fake()->countryCode(),
                'state' => FakerAddress::state(),
                'city' => fake()->city(),
                'address' => fake()->streetAddress(),
                'zip_code' => fake()->postcode(),
                'customer_id' => $customer->id,
                'is_default' => false,
            ]);
        }

        for ($i = 0; $i < 8; $i++) {
            $customer = Customer::query()->create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('12345678'),
                'phone' => fake()->e164PhoneNumber(),
                'avatar' => 'customers/' . ($i + 1) . '.jpg',
                'dob' => now()->subYears(rand(20, 50))->subDays(rand(1, 30)),
            ]);

            $customer->confirmed_at = now();
            $customer->save();

            Address::query()->create([
                'name' => $customer->name,
                'phone' => fake()->e164PhoneNumber(),
                'email' => $customer->email,
                'country' => fake()->countryCode(),
                'state' => FakerAddress::state(),
                'city' => fake()->city(),
                'address' => fake()->streetAddress(),
                'zip_code' => fake()->postcode(),
                'customer_id' => $customer->id,
                'is_default' => true,
            ]);
        }
    }
}
