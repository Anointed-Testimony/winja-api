<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            [ 'email' => 'admin@winja.com' ],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin@12345'),
                'user_type' => 'admin',
                'status' => 'active',
                'is_premium' => true,
            ]
        );
    }
} 
 
 
 
 
 
 
 
 
 
 
 