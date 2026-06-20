<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\TherapistCommissionCalculator;

class TransactionObserver
{
    public function __construct(
        private readonly TherapistCommissionCalculator $calculator,
    ) {}

    public function saved(Transaction $transaction): void
    {
        $this->calculator->sync($transaction);
    }
}
