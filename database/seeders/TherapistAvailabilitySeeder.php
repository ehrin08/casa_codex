<?php

namespace Database\Seeders;

use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use Illuminate\Database\Seeder;

class TherapistAvailabilitySeeder extends Seeder
{
    /**
     * Seed recurring fake weekly availability for development schedule placeholders.
     */
    public function run(): void
    {
        $availabilityByEmployeeCode = [
            'CP-T001' => [
                ['day_of_week' => 1, 'start_time' => '10:00:00', 'end_time' => '18:00:00'],
                ['day_of_week' => 2, 'start_time' => '10:00:00', 'end_time' => '18:00:00'],
                ['day_of_week' => 3, 'start_time' => '10:00:00', 'end_time' => '18:00:00'],
                ['day_of_week' => 4, 'start_time' => '10:00:00', 'end_time' => '18:00:00'],
                ['day_of_week' => 5, 'start_time' => '10:00:00', 'end_time' => '18:00:00'],
            ],
            'CP-T002' => [
                ['day_of_week' => 2, 'start_time' => '12:00:00', 'end_time' => '20:00:00'],
                ['day_of_week' => 3, 'start_time' => '12:00:00', 'end_time' => '20:00:00'],
                ['day_of_week' => 4, 'start_time' => '12:00:00', 'end_time' => '20:00:00'],
                ['day_of_week' => 5, 'start_time' => '12:00:00', 'end_time' => '20:00:00'],
                ['day_of_week' => 6, 'start_time' => '12:00:00', 'end_time' => '20:00:00'],
            ],
        ];

        foreach ($availabilityByEmployeeCode as $employeeCode => $windows) {
            $therapist = TherapistProfile::where('employee_code', $employeeCode)->first();

            if (! $therapist) {
                continue;
            }

            foreach ($windows as $window) {
                TherapistAvailability::updateOrCreate(
                    [
                        'therapist_profile_id' => $therapist->id,
                        'availability_date' => null,
                        'day_of_week' => $window['day_of_week'],
                    ],
                    [
                        'start_time' => $window['start_time'],
                        'end_time' => $window['end_time'],
                        'status' => 'active',
                        'notes' => 'Development/test recurring availability.',
                    ]
                );
            }
        }
    }
}
