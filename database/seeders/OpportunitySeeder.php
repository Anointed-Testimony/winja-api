<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\OpportunityType;

class OpportunitySeeder extends Seeder
{
    public function run()
    {
        $grantTypeId = OpportunityType::where('name', 'Grants')->value('id');
        $internshipTypeId = OpportunityType::where('name', 'Internships')->value('id');
        $scholarshipTypeId = OpportunityType::where('name', 'Scholarships')->value('id');

        DB::table('opportunities')->insert([
            [
                'title' => 'Women in Tech Grant',
                'sponsor' => 'TechWomen Foundation',
                'description' => 'A grant for women pursuing careers in technology.',
                'eligibility' => 'Women, 18-35, interested in tech',
                'status' => 'Active',
                'expiry' => Carbon::now()->addDays(30),
                'verified' => true,
                'opportunity_type_id' => $grantTypeId,
                'image' => null,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Remote Data Internship',
                'sponsor' => 'DataX',
                'description' => 'A remote internship for aspiring data analysts.',
                'eligibility' => 'Students and recent graduates',
                'status' => 'Active',
                'expiry' => Carbon::now()->addDays(15),
                'verified' => false,
                'opportunity_type_id' => $internshipTypeId,
                'image' => null,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'AI Research Scholarship',
                'sponsor' => 'AI Institute',
                'description' => 'Scholarship for students interested in AI research.',
                'eligibility' => 'Undergraduates, Postgraduates',
                'status' => 'Active',
                'expiry' => Carbon::now()->addDays(45),
                'verified' => true,
                'opportunity_type_id' => $scholarshipTypeId,
                'image' => null,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 