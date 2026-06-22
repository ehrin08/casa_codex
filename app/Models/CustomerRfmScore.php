<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRfmScore extends Model
{
    public const SEGMENT_CHAMPION = 'Champion';

    public const SEGMENT_LOYAL_CUSTOMER = 'Loyal Customer';

    public const SEGMENT_POTENTIAL_LOYALIST = 'Potential Loyalist';

    public const SEGMENT_NEEDS_ATTENTION = 'Needs Attention';

    public const SEGMENT_AT_RISK = 'At Risk';

    public const SEGMENT_NEW_LOW_ACTIVITY = 'New / Low Activity';

    public const SEGMENTS = [
        self::SEGMENT_CHAMPION,
        self::SEGMENT_LOYAL_CUSTOMER,
        self::SEGMENT_POTENTIAL_LOYALIST,
        self::SEGMENT_NEEDS_ATTENTION,
        self::SEGMENT_AT_RISK,
        self::SEGMENT_NEW_LOW_ACTIVITY,
    ];

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
