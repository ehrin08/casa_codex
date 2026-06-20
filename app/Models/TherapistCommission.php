<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapistCommission extends Model
{
    protected $fillable = [
        'therapist_profile_id',
        'transaction_id',
        'appointment_id',
        'commission_rate',
        'commission_amount',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function therapistProfile(): BelongsTo
    {
        return $this->belongsTo(TherapistProfile::class);
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
