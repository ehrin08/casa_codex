<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerProfileSeeder extends Seeder
{
    /**
     * Seed fake customer profiles for development screens and relationship testing.
     */
    public function run(): void
    {
        $profiles = [
            [
                'email' => 'ana.customer@example.test',
                'first_name' => 'Ana',
                'last_name' => 'Cruz',
                'phone' => '09180000001',
                'birth_date' => '1994-05-12',
                'gender' => 'female',
                'address' => 'Sample address, Bacolod City',
                'notes' => 'Development/test customer profile.',
            ],
            [
                'email' => 'miguel.customer@example.test',
                'first_name' => 'Miguel',
                'last_name' => 'Garcia',
                'phone' => '09180000002',
                'birth_date' => '1990-10-03',
                'gender' => 'male',
                'address' => 'Sample address, Iloilo City',
                'notes' => 'Development/test customer profile.',
            ],
            [
                'email' => 'walkin.customer@example.test',
                'first_name' => 'Walk-in',
                'last_name' => 'Customer',
                'phone' => '09180000003',
                'birth_date' => null,
                'gender' => null,
                'address' => 'Sample walk-in record',
                'notes' => 'Development/test walk-in style customer profile without a linked user.',
            ],
        ];

        foreach ($profiles as $profile) {
            $user = User::where('email', $profile['email'])->first();

            CustomerProfile::updateOrCreate(
                ['email' => $profile['email']],
                [
                    'user_id' => $user?->id,
                    'first_name' => $profile['first_name'],
                    'last_name' => $profile['last_name'],
                    'phone' => $profile['phone'],
                    'birth_date' => $profile['birth_date'],
                    'gender' => $profile['gender'],
                    'address' => $profile['address'],
                    'notes' => $profile['notes'],
                    'is_active' => true,
                ]
            );
        }
    }
}
