<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function isManagement(): bool
    {
        return $this->hasRole('management');
    }

    public function isTherapist(): bool
    {
        return $this->hasRole('therapist');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function dashboardRouteName(): string
    {
        return match (true) {
            $this->isManagement() => 'management.index',
            $this->isTherapist() => 'therapist.index',
            $this->isCustomer() => 'customer.index',
            default => 'home',
        };
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function therapistProfile(): HasOne
    {
        return $this->hasOne(TherapistProfile::class);
    }

    public function therapistCommissions(): HasMany
    {
        return $this->hasMany(TherapistCommission::class, 'therapist_user_id');
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class, 'recipient_user_id');
    }
}
