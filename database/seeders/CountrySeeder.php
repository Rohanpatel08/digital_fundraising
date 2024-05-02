<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */


    public function run(): void
    {
        $data = [
            [
                'country_name' => 'US',
                // Add more columns and values as needed
            ],
            [
                'country_name' => 'Canada',
                // Add more columns and values as needed
            ],
        ];

        Country::insert($data);
    }
}