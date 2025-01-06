<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Contact\Enums\ContactStatusEnum;
use Botble\Contact\Models\Contact;

class ContactSeeder extends BaseSeeder
{
    public function run(): void
    {
        Contact::query()->truncate();

        for ($i = 0; $i < 10; $i++) {
            Contact::query()->create([
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->address(),
                'subject' => fake()->text(50),
                'content' => fake()->text(500),
                'status' => fake()->randomElement([ContactStatusEnum::READ, ContactStatusEnum::UNREAD]),
            ]);
        }
    }
}
