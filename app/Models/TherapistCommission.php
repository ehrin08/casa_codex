<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapistCommission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_VOID,
    ];

    protected $fillable = [
        'therapist_profile_id',
        'therapist_user_id',
        'transaction_id',
        'appointment_id',
        'commission_rate',
        'commission_base_amount',
        'commission_amount',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_base_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function therapistProfile(): BelongsTo
    {
        return $this->belongsTo(TherapistProfile::class);
    }

    public function therapistUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_user_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
