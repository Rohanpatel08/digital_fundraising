<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'plan_type' => '1',
                'plan_name' => 'weekly.plan',
                // Add more columns and values as needed
            ],
            [
                'plan_type' => '2',
                'plan_name' => 'monthly.plan',
                // Add more columns and values as needed
            ],
            [
                'plan_type' => '3',
                'plan_name' => 'yearly.plan',
                // Add more columns and values as needed
            ],
        ];

        Plan::insert($data);
    }
}
