<?php

declare(strict_types=1);

namespace Credit\Core\Scoring;

use Credit\Core\Catalog\PartnerLogoUrlResolver;
use Credit\Core\DTO\Response\AdjustmentSuggestionDto;
use Credit\Core\DTO\Response\QuoteOfferDto;
use Credit\Core\Enum\AdjustmentKindEnum;
use Credit\Core\Scoring\Model\OfferEvaluation;

/**
 * Construit les DTO exposés au front à partir d'une évaluation de produit.
 */
final readonly class QuoteFactory
{
    public function __construct(
        private PartnerLogoUrlResolver $logoUrlResolver,
    ) {}

    public function quote(OfferEvaluation $evaluation, bool $isBestRate = false): QuoteOfferDto
    {
        $offer = $evaluation->offer;
        $schedule = $evaluation->schedule;

        return new QuoteOfferDto(
            partnerCode: $offer->partnerCode,
            partnerName: $offer->partnerName,
            partnerLogo: $this->logoUrlResolver->resolve($offer->partnerLogo),
            offerCode: $offer->offerCode,
            productName: $offer->productName,
            projectType: $offer->projectType,
            amountCents: $schedule->amountCents,
            durationMonths: $schedule->durationMonths,
            nominalRateBps: $offer->nominalRateBps,
            aprBps: $offer->aprBps,
            monthlyInstallmentCents: $schedule->monthlyInstallmentCents,
            monthlyInsuranceCents: $schedule->monthlyInsuranceCents,
            totalInterestCents: $schedule->totalInterestCents,
            totalFinancingCostCents: $schedule->totalFinancingCostCents,
            debtRatioAfterBps: $evaluation->debtRatioAfterBps,
            isBestRate: $isBestRate,
        );
    }

    public function suggestion(OfferEvaluation $evaluation, AdjustmentKindEnum $kind, string $label, int $distanceBps): AdjustmentSuggestionDto
    {
        $offer = $evaluation->offer;

        return new AdjustmentSuggestionDto(
            kind: $kind,
            label: $label,
            partnerCode: $offer->partnerCode,
            partnerName: $offer->partnerName,
            partnerLogo: $this->logoUrlResolver->resolve($offer->partnerLogo),
            offerCode: $offer->offerCode,
            productName: $offer->productName,
            amountCents: $evaluation->amountCents(),
            durationMonths: $evaluation->durationMonths(),
            monthlyInstallmentCents: $evaluation->schedule->monthlyInstallmentCents,
            aprBps: $offer->aprBps,
            debtRatioAfterBps: $evaluation->debtRatioAfterBps,
            distanceBps: $distanceBps,
        );
    }
}
