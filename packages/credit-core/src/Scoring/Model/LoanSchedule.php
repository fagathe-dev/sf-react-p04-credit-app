<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Model;

/**
 * Échéancier à mensualité constante, en centimes.
 */
final readonly class LoanSchedule
{
    public function __construct(
        public int $amountCents,
        public int $durationMonths,
        public int $nominalRateBps,
        public int $monthlyInstallmentCents,
        public int $monthlyInsuranceCents,
        public int $totalInterestCents,
        public int $totalInsuranceCents,
        /** Intérêts + assurance sur toute la durée. */
        public int $totalFinancingCostCents,
    ) {}
}
