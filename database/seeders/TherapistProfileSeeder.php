<?php

namespace Database\Seeders;

use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class TherapistProfileSeeder extends Seeder
{
    /**
     * Seed fake therapist profiles for development screens and relationship testing.
     */
    public function run(): void
    {
        $profiles = [
            [
                'email' => 'maya.therapist@example.test',
                'employee_code' => 'CP-T001',
                'first_name' => 'Maya',
                'last_name' => 'Santos',
                'phone' => '09170000001',
                'specialty' => 'Swedish massage and relaxation therapy',
                'commission_rate' => 20,
                'status' => 'active',
                'hired_at' => '2026-01-15',
                'notes' => 'Development/test therapist profile.',
            ],
            [
                'email' => 'leo.therapist@example.test',
                'employee_code' => 'CP-T002',
                'first_name' => 'Leo',
                'last_name' => 'Reyes',
                'phone' => '09170000002',
                'specialty' => 'Deep tissue and ventosa massage',
                'commission_rate' => 22,
                'status' => 'active',
                'hired_at' => '2026-02-01',
                'notes' => 'Development/test therapist profile.',
            ],
        ];

        foreach ($profiles as $profile) {
            $user = User::where('email', $profile['email'])->first();

            TherapistProfile::updateOrCreate(
                ['employee_code' => $profile['employee_code']],
                [
                    'user_id' => $user?->id,
                    'first_name' => $profile['first_name'],
                    'last_name' => $profile['last_name'],
                    'email' => $profile['email'],
                    'phone' => $profile['phone'],
                    'specialty' => $profile['specialty'],
                    'commission_rate' => $profile['commission_rate'],
                    'status' => $profile['status'],
                    'hired_at' => $profile['hired_at'],
                    'notes' => $profile['notes'],
                ]
            );
        }
    }
}
