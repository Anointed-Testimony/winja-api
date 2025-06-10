<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OpportunityType;

class OpportunityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Job Openings',
                'description' => 'Full-time, part-time, and contract employment opportunities'
            ],
            [
                'name' => 'Scholarships',
                'description' => 'Financial aid and educational funding opportunities'
            ],
            [
                'name' => 'Discounts and Promos',
                'description' => 'Special offers and promotional deals for students and young professionals'
            ],
            [
                'name' => 'Grants',
                'description' => 'Funding opportunities for projects, research, and initiatives'
            ],
            [
                'name' => 'Freelance Gigs',
                'description' => 'Independent work and project-based opportunities'
            ],
            [
                'name' => 'Internships',
                'description' => 'Professional training and work experience opportunities'
            ],
            [
                'name' => 'Competitions and Prizes',
                'description' => 'Contests, challenges, and prize-winning opportunities'
            ],
            [
                'name' => 'Free Training and Workshops',
                'description' => 'Educational and skill development programs'
            ],
            [
                'name' => 'Government Programs',
                'description' => 'Opportunities provided by government agencies and departments'
            ],
            [
                'name' => 'Tech Hackathons and Bootcamps',
                'description' => 'Technology-focused events and intensive training programs'
            ],
            [
                'name' => 'Business Pitch Opportunities',
                'description' => 'Chances to present business ideas and secure funding'
            ],
            [
                'name' => 'Study Abroad Programs',
                'description' => 'International education and cultural exchange opportunities'
            ],
            [
                'name' => 'Fellowships',
                'description' => 'Advanced professional development and research opportunities'
            ],
            [
                'name' => 'Volunteering',
                'description' => 'Community service and social impact opportunities'
            ],
            [
                'name' => 'Networking Events',
                'description' => 'Professional networking and community building events'
            ],
            [
                'name' => 'Mentorship Programs',
                'description' => 'Guidance and support from experienced professionals'
            ],
            [
                'name' => 'Calls for Papers',
                'description' => 'Academic and research publication opportunities'
            ],
            [
                'name' => 'Social Impact Initiatives',
                'description' => 'Projects and programs focused on creating positive social change'
            ],
            [
                'name' => 'Flash Sales',
                'description' => 'Limited-time offers and special deals'
            ],
        ];

        foreach ($types as $type) {
            OpportunityType::create($type);
        }
    }
} 