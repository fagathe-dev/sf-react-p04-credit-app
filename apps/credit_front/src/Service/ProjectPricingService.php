<?php

declare(strict_types=1);

namespace App\Service;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\Catalog\InMemoryProductCatalog;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Scoring\Calculator\LoanMathCalculator;

/**
 * Barèmes indicatifs d'un projet, lus dans le catalogue : TAEG « à partir de », bornes et exemple représentatif.
 */
final readonly class ProjectPricingService
{
    public function __construct(
        private InMemoryProductCatalog $catalog,
        private LoanMathCalculator $loanMath,
    ) {}

    public function forProject(CreditProjectTypeEnum $project): ?ProjectPricing
    {
        $offers = $this->catalog->getOffersByProject($project);
        $best = $this->catalog->findLowestAprOffer($project);

        if (null === $best || [] === $offers) {
            return null;
        }

        $schedule = $this->loanMath->schedule($best->referenceAmountCents, $best->referenceDurationMonths, $best->nominalRateBps, $best->insuranceRateBps);

        return new ProjectPricing(
            project: $project,
            fromAprBps: $best->aprBps,
            offersCount: \count($offers),
            partners: array_values(array_unique(array_map(static fn (OfferConfigDto $o): string => $o->partnerName, $offers))),
            minAmountCents: min(array_map(static fn (OfferConfigDto $o): int => $o->criteria->minAmountCents, $offers)),
            maxAmountCents: max(array_map(static fn (OfferConfigDto $o): int => $o->criteria->maxAmountCents, $offers)),
            minDurationMonths: min(array_map(static fn (OfferConfigDto $o): int => $o->criteria->minDurationMonths, $offers)),
            maxDurationMonths: max(array_map(static fn (OfferConfigDto $o): int => $o->criteria->maxDurationMonths, $offers)),
            exampleOffer: $best,
            exampleMonthlyInstallmentCents: $schedule->monthlyInstallmentCents,
            exampleTotalInterestCents: $schedule->totalInterestCents,
            exampleTotalRepaidCents: $schedule->monthlyInstallmentCents * $schedule->durationMonths,
        );
    }

    /**
     * @return list<ProjectPricing>
     */
    public function all(): array
    {
        return array_values(array_filter(array_map($this->forProject(...), CreditProjectTypeEnum::cases())));
    }
}
