<?php

declare(strict_types=1);

namespace Credit\Core\Tests\Scoring;

use Credit\Core\Scoring\Calculator\DebtRatioCalculator;
use Credit\Core\Scoring\Calculator\LoanMathCalculator;
use Credit\Core\DTO\Request\IncomesInputDto;
use Credit\Core\Util\FrenchFormatter;
use Credit\Core\Util\UnitConverter;
use PHPUnit\Framework\TestCase;

final class LoanMathCalculatorTest extends TestCase
{
    public function testMonthlyInstallmentMatchesAnnuityFormula(): void
    {
        $calculator = new LoanMathCalculator();

        // 10 000 € sur 12 mois à 12 % : 888,49 €
        self::assertSame(88_849, $calculator->monthlyInstallment(1_000_000, 12, 1_200));
        // Taux nul : capital / durée, arrondi au centime supérieur
        self::assertSame(83_334, $calculator->monthlyInstallment(1_000_000, 12, 0));
    }

    public function testScheduleAggregatesInterestAndInsurance(): void
    {
        $schedule = (new LoanMathCalculator())->schedule(1_000_000, 12, 1_200, 60);

        self::assertSame(88_849 * 12 - 1_000_000, $schedule->totalInterestCents);
        self::assertSame(500, $schedule->monthlyInsuranceCents); // 10 000 € × 0,60 % / 12
        self::assertSame($schedule->totalInterestCents + 6_000, $schedule->totalFinancingCostCents);
    }

    public function testEffectiveAnnualRate(): void
    {
        self::assertSame(540, (new LoanMathCalculator())->effectiveAnnualRateBps(527));
    }

    public function testRentalIncomeIsWeightedAt70Percent(): void
    {
        $income = (new DebtRatioCalculator())->weightedIncome(new IncomesInputDto(netMonthlySalaryCents: 200_000, rentalIncomeGrossCents: 100_000), false);

        self::assertSame(270_000, $income);
    }

    public function testHouseholdMinRemainder(): void
    {
        $calculator = new DebtRatioCalculator();

        self::assertSame(75_000, $calculator->householdMinRemainder(false, 0));
        self::assertSame(160_000, $calculator->householdMinRemainder(true, 2));
    }

    public function testUnitsAndFormatting(): void
    {
        self::assertSame(380, UnitConverter::percentToBps(3.8));
        self::assertSame(423, UnitConverter::percentToBps(4.23));
        self::assertSame(1_500_000, UnitConverter::eurosToCents(15000));
        self::assertSame(15_397, UnitConverter::eurosToCents(153.97));
        self::assertSame("37,2\u{00A0}%", FrenchFormatter::bps(3_720));
        self::assertSame("3,80\u{00A0}%", FrenchFormatter::bps(380, 2));
        self::assertSame("15\u{202F}000,00\u{00A0}€", FrenchFormatter::cents(1_500_000));
    }
}
