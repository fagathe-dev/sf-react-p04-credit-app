<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Calculator;

use Credit\Core\Scoring\Model\LoanSchedule;
use Credit\Core\Util\Decimal;

/**
 * Mensualité constante, coût total et TAEG, en arithmétique décimale exacte (bcmath).
 *
 * - Mensualité : M = C × t / (1 − (1 + t)^−n), t = taux débiteur annuel / 12.
 * - Assurance (hypothèse : taux annuel sur le capital initial) : C × taux assurance / 12 par mois.
 * - Coût total du crédit : intérêts + assurance sur toute la durée.
 */
final class LoanMathCalculator
{
    private const int BPS = 10_000;

    public function schedule(int $amountCents, int $durationMonths, int $nominalRateBps, int $insuranceRateBps = 0): LoanSchedule
    {
        if ($amountCents <= 0 || $durationMonths <= 0) {
            throw new \InvalidArgumentException('Le montant et la durée doivent être strictement positifs.');
        }

        $monthly = $this->monthlyInstallment($amountCents, $durationMonths, $nominalRateBps);
        $totalInterest = max(0, $monthly * $durationMonths - $amountCents);
        $monthlyInsurance = $this->monthlyInsurance($amountCents, $insuranceRateBps);
        $totalInsurance = $monthlyInsurance * $durationMonths;

        return new LoanSchedule(
            amountCents: $amountCents,
            durationMonths: $durationMonths,
            nominalRateBps: $nominalRateBps,
            monthlyInstallmentCents: $monthly,
            monthlyInsuranceCents: $monthlyInsurance,
            totalInterestCents: $totalInterest,
            totalInsuranceCents: $totalInsurance,
            totalFinancingCostCents: $totalInterest + $totalInsurance,
        );
    }

    /**
     * Mensualité hors assurance, en centimes (arrondie au centime le plus proche).
     */
    public function monthlyInstallment(int $amountCents, int $durationMonths, int $nominalRateBps): int
    {
        if (0 === $nominalRateBps) {
            return Decimal::ceilToInt(bcdiv((string) $amountCents, (string) $durationMonths, Decimal::SCALE));
        }

        $monthlyRate = $this->monthlyRate($nominalRateBps);
        $growth = bcpow(bcadd('1', $monthlyRate, Decimal::SCALE), (string) $durationMonths, Decimal::SCALE);

        $numerator = bcmul(bcmul((string) $amountCents, $monthlyRate, Decimal::SCALE), $growth, Decimal::SCALE);
        $denominator = bcsub($growth, '1', Decimal::SCALE);

        return Decimal::roundToInt(bcdiv($numerator, $denominator, Decimal::SCALE));
    }

    public function monthlyInsurance(int $amountCents, int $insuranceRateBps): int
    {
        if ($insuranceRateBps <= 0) {
            return 0;
        }

        return Decimal::divRound($amountCents * $insuranceRateBps, self::BPS * 12);
    }

    /**
     * Taux annuel effectif correspondant à un taux débiteur (sans frais) : (1 + t)^12 − 1, en points de base.
     */
    public function effectiveAnnualRateBps(int $nominalRateBps): int
    {
        $growth = bcpow(bcadd('1', $this->monthlyRate($nominalRateBps), Decimal::SCALE), '12', Decimal::SCALE);

        return Decimal::roundToInt(bcmul(bcsub($growth, '1', Decimal::SCALE), (string) self::BPS, Decimal::SCALE));
    }

    private function monthlyRate(int $nominalRateBps): string
    {
        return bcdiv((string) $nominalRateBps, (string) (self::BPS * 12), Decimal::SCALE);
    }
}
