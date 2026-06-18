<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapistAvailability extends Model
{
    protected $fillable = [
        'therapist_profile_id',
        'availability_date',
        'day_of_week',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'availability_date' => 'date',
    ];

    public function therapistProfile(): BelongsTo
    {
        return $this->belongsTo(TherapistProfile::class);
    }
}
