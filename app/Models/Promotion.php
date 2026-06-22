<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    public const DISCOUNT_TYPE_FIXED = 'fixed';

    public const DISCOUNT_TYPES = [
        self::DISCOUNT_TYPE_PERCENTAGE,
        self::DISCOUNT_TYPE_FIXED,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    public const RFM_SEGMENTS = CustomerRfmScore::SEGMENTS;

    protected $fillable = [
        'title',
        'description',
        'discount_type',
        'discount_value',
        'rfm_segment_label',
        'rule_min_recency_score',
        'rule_min_frequency_score',
        'rule_min_monetary_score',
        'rule_payload',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'rule_payload' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }
}
