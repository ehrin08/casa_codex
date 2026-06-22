<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReview extends Model
{
    public const SENTIMENT_POSITIVE = 'positive';

    public const SENTIMENT_NEUTRAL = 'neutral';

    public const SENTIMENT_NEGATIVE = 'negative';

    public const SENTIMENTS = [
        self::SENTIMENT_POSITIVE,
        self::SENTIMENT_NEUTRAL,
        self::SENTIMENT_NEGATIVE,
    ];

    protected $fillable = [
        'customer_profile_id',
        'appointment_id',
        'service_id',
        'rating',
        'comment',
        'sentiment_label',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
