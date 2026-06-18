<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed development/test role records used by future access control.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'management',
                'display_name' => 'Management',
                'description' => 'Spa owner, admin, and cashier-level management access.',
            ],
            [
                'name' => 'therapist',
                'display_name' => 'Therapist',
                'description' => 'Therapist/staff access for schedules, services, and commissions.',
            ],
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'description' => 'Customer access for profile, appointments, reviews, and notifications.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
