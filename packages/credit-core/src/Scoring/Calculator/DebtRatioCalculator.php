<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Calculator;

use Credit\Core\DTO\Request\ChargesInputDto;
use Credit\Core\DTO\Request\IncomesInputDto;
use Credit\Core\Util\Decimal;

/**
 * Pondération des revenus, agrégation des charges, taux d'endettement et reste à vivre.
 */
final class DebtRatioCalculator
{
    private const int BPS = 10_000;

    /** Revenus locatifs bruts retenus à 70 % (abattement bancaire forfaitaire de 30 %). */
    public const int RENTAL_INCOME_WEIGHT_BPS = 7_000;

    public const int SINGLE_MIN_REMAINDER_CENTS = 75_000;
    public const int COUPLE_MIN_REMAINDER_CENTS = 110_000;
    public const int PER_CHILD_MIN_REMAINDER_CENTS = 25_000;

    /** Endettement retenu lorsque le foyer n'a aucun revenu (1000 %). */
    public const int NO_INCOME_DEBT_RATIO_BPS = 100_000;

    public function weightedIncome(IncomesInputDto $incomes, bool $hasCoBorrower): int
    {
        return $incomes->netMonthlySalaryCents
            + ($hasCoBorrower ? $incomes->coBorrowerNetSalaryCents : 0)
            + $incomes->familyAllowancesCents
            + $incomes->pensionsReceivedCents
            + Decimal::divRound($incomes->rentalIncomeGrossCents * self::RENTAL_INCOME_WEIGHT_BPS, self::BPS)
            + $incomes->otherMonthlyIncomeCents;
    }

    public function totalCharges(ChargesInputDto $charges): int
    {
        return $charges->rentMonthlyCents
            + $charges->mortgageMonthlyCents
            + $charges->consumerLoansMonthlyCents
            + $charges->alimonyPaidMonthlyCents
            + $charges->otherChargesMonthlyCents;
    }

    /**
     * Taux d'endettement en points de base : charges / revenus pondérés.
     */
    public function debtRatioBps(int $chargesCents, int $weightedIncomeCents): int
    {
        if ($weightedIncomeCents <= 0) {
            return $chargesCents > 0 ? self::NO_INCOME_DEBT_RATIO_BPS : 0;
        }

        return Decimal::divRound($chargesCents * self::BPS, $weightedIncomeCents);
    }

    public function remainderToLive(int $weightedIncomeCents, int $chargesCents, int $newInstallmentCents = 0): int
    {
        return $weightedIncomeCents - $chargesCents - $newInstallmentCents;
    }

    /**
     * Seuil minimal de reste à vivre : 750 € seul, 1 100 € à deux, + 250 € par enfant à charge.
     */
    public function householdMinRemainder(bool $hasCoBorrower, int $dependentChildrenCount): int
    {
        $base = $hasCoBorrower ? self::COUPLE_MIN_REMAINDER_CENTS : self::SINGLE_MIN_REMAINDER_CENTS;

        return $base + max(0, $dependentChildrenCount) * self::PER_CHILD_MIN_REMAINDER_CENTS;
    }
}
