<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\TherapistCommissionCalculator;

class AppointmentObserver
{
    public function __construct(
        private readonly TherapistCommissionCalculator $calculator,
    ) {}

    public function saved(Appointment $appointment): void
    {
        if (! $appointment->wasChanged(['status', 'therapist_profile_id'])) {
            return;
        }

        $transaction = $appointment->transaction()->first();

        if ($transaction) {
            $this->calculator->sync($transaction);
        }
    }
}
