<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_NO_SHOW,
    ];

    public const CONFLICT_BLOCKING_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'customer_profile_id',
        'guest_name',
        'guest_contact',
        'is_walk_in',
        'therapist_profile_id',
        'service_id',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'service_name_snapshot',
        'service_duration_minutes_snapshot',
        'service_price_snapshot',
        'notes',
        'cancellation_reason',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'service_price_snapshot' => 'decimal:2',
        'is_walk_in' => 'boolean',
    ];

    public function getCustomerDisplayNameAttribute(): string
    {
        if ($this->customerProfile) {
            return trim($this->customerProfile->first_name.' '.$this->customerProfile->last_name)
                ?: 'Customer unavailable';
        }

        if (filled($this->guest_name)) {
            return $this->guest_name;
        }

        return $this->is_walk_in ? 'Walk-in Guest' : 'Customer unavailable';
    }

    public function getCustomerDisplayLabelAttribute(): ?string
    {
        if (! $this->is_walk_in) {
            return null;
        }

        return $this->customer_profile_id ? 'Existing Customer' : 'Walk-in Guest';
    }

    public function getCustomerDisplayContactAttribute(): ?string
    {
        if ($this->customerProfile?->phone) {
            return $this->customerProfile->phone;
        }

        return $this->guest_contact;
    }

    public function getIsWalkInGuestAttribute(): bool
    {
        return $this->is_walk_in && $this->customer_profile_id === null;
    }

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    public function therapistProfile(): BelongsTo
    {
        return $this->belongsTo(TherapistProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(TherapistCommission::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(CustomerReview::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(AppointmentStatusHistory::class);
    }
}
