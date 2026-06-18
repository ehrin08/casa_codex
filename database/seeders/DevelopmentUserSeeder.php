<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentUserSeeder extends Seeder
{
    /**
     * Seed fake development users only.
     *
     * Test password for all users: password
     */
    public function run(): void
    {
        $roleIds = Role::query()
            ->whereIn('name', ['management', 'therapist', 'customer'])
            ->pluck('id', 'name');

        $users = [
            [
                'role' => 'management',
                'name' => 'Management Test User',
                'email' => 'management@example.test',
            ],
            [
                'role' => 'therapist',
                'name' => 'Maya Santos',
                'email' => 'maya.therapist@example.test',
            ],
            [
                'role' => 'therapist',
                'name' => 'Leo Reyes',
                'email' => 'leo.therapist@example.test',
            ],
            [
                'role' => 'customer',
                'name' => 'Ana Cruz',
                'email' => 'ana.customer@example.test',
            ],
            [
                'role' => 'customer',
                'name' => 'Miguel Garcia',
                'email' => 'miguel.customer@example.test',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'role_id' => $roleIds[$user['role']] ?? null,
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
