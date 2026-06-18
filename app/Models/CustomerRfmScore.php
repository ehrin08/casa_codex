<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRfmScore extends Model
{
    protected $fillable = [
        'customer_profile_id',
        'recency_score',
        'frequency_score',
        'monetary_score',
        'segment_label',
        'calculated_at',
        'source_notes',
    ];

    protected $casts = [
        'calculated_at' => 'date',
    ];

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }
}
