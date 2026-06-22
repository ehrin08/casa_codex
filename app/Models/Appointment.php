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
    ];

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
