<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PartnerSeeder extends Seeder
{
    public function run()
    {
        $partners = [
            [
                'name' => 'UBA Foundation',
                'email' => 'uba@partners.com',
                'password' => Hash::make('password'),
                'user_type' => 'partner',
                'company_name' => 'UBA Foundation',
                'company_description' => 'UBA Foundation is a leading African philanthropic organization.',
                'company_website' => 'https://www.ubafoundation.com',
                'company_logo' => null,
                'partner_since' => now(),
                'partner_status' => 'active',
                'status' => 'active',
            ],
            [
                'name' => 'TechWomen',
                'email' => 'techwomen@partners.com',
                'password' => Hash::make('password'),
                'user_type' => 'partner',
                'company_name' => 'TechWomen',
                'company_description' => 'TechWomen empowers, connects, and supports the next generation of women leaders in STEM.',
                'company_website' => 'https://www.techwomen.org',
                'company_logo' => null,
                'partner_since' => now(),
                'partner_status' => 'active',
                'status' => 'active',
            ],
            [
                'name' => 'Lagos State',
                'email' => 'lagos@partners.com',
                'password' => Hash::make('password'),
                'user_type' => 'partner',
                'company_name' => 'Lagos State',
                'company_description' => 'Lagos State Government official partner.',
                'company_website' => 'https://www.lagosstate.gov.ng',
                'company_logo' => null,
                'partner_since' => now(),
                'partner_status' => 'active',
                'status' => 'active',
            ],
        ];

        foreach ($partners as $partner) {
            User::updateOrCreate(
                ['email' => $partner['email']],
                $partner
            );
        }
    }
} 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 