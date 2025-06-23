<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdSettings;

class AdSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'ad_type' => 'featured',
                'duration_type' => 'daily',
                'price' => 2000,
                'description' => 'Featured Ad (Daily)',
                'is_active' => true,
            ],
            [
                'ad_type' => 'featured',
                'duration_type' => 'weekly',
                'price' => 7000,
                'description' => 'Featured Ad (Weekly)',
                'is_active' => true,
            ],
            [
                'ad_type' => 'inline',
                'duration_type' => 'daily',
                'price' => 1500,
                'description' => 'Inline Ad (Daily)',
                'is_active' => true,
            ],
            [
                'ad_type' => 'inline',
                'duration_type' => 'weekly',
                'price' => 5000,
                'description' => 'Inline Ad (Weekly)',
                'is_active' => true,
            ],
        ];

        foreach ($data as $item) {
            AdSettings::updateOrCreate(
                [
                    'ad_type' => $item['ad_type'],
                    'duration_type' => $item['duration_type'],
                ],
                $item
            );
        }
    }
} 