<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run()
    {
        SubscriptionPlan::create([
            'name' => 'Basic Plan',
            'price' => 1000.00,
            'duration' => 'monthly',
            'features' => [
                'Unlimited opportunity uploads',
                'Priority listing',
                'Basic analytics',
                'Email support'
            ]
        ]);

        SubscriptionPlan::create([
            'name' => 'Premium Plan',
            'price' => 2500.00,
            'duration' => 'quarterly',
            'features' => [
                'Unlimited opportunity uploads',
                'Priority listing',
                'Advanced analytics',
                'Premium support',
                'Featured opportunities',
                'Custom branding'
            ]
        ]);

        SubscriptionPlan::create([
            'name' => 'Pro Plan',
            'price' => 8000.00,
            'duration' => 'yearly',
            'features' => [
                'Unlimited opportunity uploads',
                'Priority listing',
                'Advanced analytics',
                'Premium support',
                'Featured opportunities',
                'Custom branding',
                'API access',
                'White-label solution'
            ]
        ]);
    }
} 