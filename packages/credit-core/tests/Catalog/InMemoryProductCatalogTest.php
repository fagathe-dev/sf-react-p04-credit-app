<?php

declare(strict_types=1);

namespace Credit\Core\Tests\Catalog;

use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Scoring\Calculator\LoanMathCalculator;
use Credit\Core\Tests\CoreFactory;
use PHPUnit\Framework\TestCase;

final class InMemoryProductCatalogTest extends TestCase
{
    public function testCatalogHasFivePartnersAndTwentyProducts(): void
    {
        $catalog = CoreFactory::catalog();

        self::assertCount(5, $catalog->getPartners());
        self::assertCount(20, $catalog->getOffers());

        $codes = array_map(static fn ($offer) => $offer->offerCode, $catalog->getOffers());
        self::assertCount(20, array_unique($codes));
    }

    public function testValuesAreConvertedToCentsAndBasisPoints(): void
    {
        $offer = CoreFactory::catalog()->findOffer('ALTIS_AUTO_PREMIUM');

        self::assertNotNull($offer);
        self::assertSame(380, $offer->aprBps);
        self::assertSame(500_000, $offer->criteria->minAmountCents);
        self::assertSame(5_000_000, $offer->criteria->maxAmountCents);
        self::assertSame(210_000, $offer->criteria->minMonthlyIncomeCents);
        self::assertSame(3_000, $offer->criteria->maxDebtRatioBps);
        self::assertSame('Altis Finance', $offer->partnerName);
    }

    public function testRepresentativeExamplesAreConsistentWithTheCalculator(): void
    {
        $calculator = new LoanMathCalculator();

        foreach (CoreFactory::catalog()->getOffers() as $offer) {
            $schedule = $calculator->schedule($offer->referenceAmountCents, $offer->referenceDurationMonths, $offer->nominalRateBps, $offer->insuranceRateBps);

            self::assertSame($schedule->monthlyInstallmentCents, $offer->referenceMonthlyInstallmentCents, $offer->offerCode);
            self::assertSame($schedule->totalFinancingCostCents, $offer->referenceTotalFinancingCostCents, $offer->offerCode);
            self::assertLessThanOrEqual(1, abs($calculator->effectiveAnnualRateBps($offer->nominalRateBps) - $offer->aprBps), $offer->offerCode);
        }
    }

    public function testEveryProjectHasOffers(): void
    {
        $catalog = CoreFactory::catalog();

        foreach (CreditProjectTypeEnum::cases() as $project) {
            self::assertNotEmpty($catalog->getOffersByProject($project), $project->value);
        }

        self::assertSame('ALTIS_GREEN_HORIZON', $catalog->findLowestAprOffer(CreditProjectTypeEnum::GREEN_LOAN)?->offerCode);
    }
}
