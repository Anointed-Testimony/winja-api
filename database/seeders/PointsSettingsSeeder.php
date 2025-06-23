<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PointsSetting;

class PointsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if points settings already exist
        if (PointsSetting::count() > 0) {
            $this->command->info('Points settings already exist. Skipping...');
            return;
        }

        // Create default points settings
        PointsSetting::create([
            'point_value_in_naira' => 1.00, // 1 point = ₦1.00
            'minimum_withdrawal' => 1000.00, // Minimum ₦1000 to withdraw
            'signup_points' => 100, // 100 points for new user registration
            'referral_points' => 500, // 500 points for successful referral
        ]);

        $this->command->info('Points settings seeded successfully!');
        $this->command->info('Default values:');
        $this->command->info('- 1 point = ₦1.00');
        $this->command->info('- Minimum withdrawal: ₦1000.00');
        $this->command->info('- Signup bonus: 100 points');
        $this->command->info('- Referral bonus: 500 points');
    }
} 