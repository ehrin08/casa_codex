<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ManagementProfileAccountService
{
    public function availableUsers(string $roleName, string $profileRelation, ?Model $profile = null): Collection
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', $roleName))
            ->with($profileRelation)
            ->where(function ($query) use ($profileRelation, $profile) {
                $query->whereDoesntHave($profileRelation);

                if ($profile?->user_id) {
                    $query->orWhereKey($profile->user_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $profileData
     */
    public function createLinkedAccount(string $roleName, array $profileData, string $password): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $name = trim(implode(' ', array_filter([
            $profileData['first_name'] ?? null,
            $profileData['last_name'] ?? null,
        ])));

        return User::create([
            'role_id' => $role->id,
            'name' => $name,
            'email' => $profileData['email'],
            'password' => $password,
        ]);
    }
}
