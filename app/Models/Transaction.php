<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    public const PAYMENT_METHOD_CASH = 'cash';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    public const PAYMENT_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_VOID,
    ];

    protected $fillable = [
        'appointment_id',
        'customer_profile_id',
        'cashier_user_id',
        'paid_by_user_id',
        'subtotal',
        'discount_amount',
        'total_amount',
        'amount_tendered',
        'change_due',
        'payment_method',
        'payment_status',
        'paid_at',
        'transaction_date',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_due' => 'decimal:2',
        'transaction_date' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function therapistCommissions(): HasMany
    {
        return $this->hasMany(TherapistCommission::class);
    }

    public function therapistCommission(): HasOne
    {
        return $this->hasOne(TherapistCommission::class);
    }

    public function promotionUsages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }
}
