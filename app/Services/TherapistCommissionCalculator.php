<?php

namespace App\Services;

use InvalidArgumentException;

class TherapistCommissionCalculator
{
    public function calculate(mixed $basisAmount, mixed $percentageRate): string
    {
        $basisCents = $this->toCents($basisAmount);
        $rateBasisPoints = (int) round(((float) $percentageRate) * 100);

        if ($basisCents < 0) {
            throw new InvalidArgumentException('The commission basis cannot be negative.');
        }

        if ($rateBasisPoints < 0 || $rateBasisPoints > 10000) {
            throw new InvalidArgumentException('The commission rate must be between 0 and 100 percent.');
        }

        $commissionCents = (int) round(($basisCents * $rateBasisPoints) / 10000);

        return number_format($commissionCents / 100, 2, '.', '');
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
