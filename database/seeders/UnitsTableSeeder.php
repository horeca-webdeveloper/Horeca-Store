<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('units')->insert([
            ['symbol' => 'cm', 'full_name' => 'centimeters'],
            ['symbol' => 'm', 'full_name' => 'meters'],
            ['symbol' => 'in', 'full_name' => 'inches'],
            ['symbol' => 'ft', 'full_name' => 'feet'],
        ]);
    }
}
