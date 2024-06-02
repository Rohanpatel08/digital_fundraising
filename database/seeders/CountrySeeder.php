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
                'id' => '64cf1f83-592e-43c9-afb6-d3be5fb4415e',
                'country_name' => 'US',
                // Add more columns and values as needed
            ],
            [
                'id' => '8ce6f5dc-1f9e-430c-a40d-02d31e1d38f3',
                'country_name' => 'Canada',
                // Add more columns and values as needed
            ],
        ];

        Country::insert($data);
    }
}
