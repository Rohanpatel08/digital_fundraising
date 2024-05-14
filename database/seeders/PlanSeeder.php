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
                'id' => '223c0fdf-975b-441e-b76e-76d9496e35c5',
                'plan_type' => '1',
                'plan_name' => 'weekly.plan',
                'campaign_limit' => 10
                // Add more columns and values as needed
            ],
            [
                'id' => '4e79335b-e050-4d92-abb9-7f7f0d60b194',
                'plan_type' => '2',
                'plan_name' => 'monthly.plan',
                'campaign_limit' => 20
                // Add more columns and values as needed
            ],
            [
                'id' => 'e249ef67-5e19-46d1-92c1-e95250eba910',
                'plan_type' => '3',
                'plan_name' => 'yearly.plan',
                'campaign_limit' => 1000
                // Add more columns and values as needed
            ],
        ];

        Plan::insert($data);
    }
}
