<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Http\ApiResponseTrait;
use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\Catalog\DTO\PartnerConfigDto;
use Credit\Core\Catalog\InMemoryProductCatalog;
use Credit\Core\Catalog\PartnerLogoUrlResolver;

/**
 * GET /api/v1/catalog : partenaires, bornes des produits et taux d'appel (simulateur rapide).
 */
final class CatalogService
{
    use ApiResponseTrait;

    public function __construct(
        private readonly InMemoryProductCatalog $catalog,
        private readonly PartnerLogoUrlResolver $logoUrlResolver,
    ) {}

    public function getCatalog(): object
    {
        $partners = array_map(fn (PartnerConfigDto $partner): array => [
            'code' => $partner->code,
            'name' => $partner->name,
            'logo' => $this->logoUrlResolver->resolve($partner->logo),
            'offers' => array_map(static fn (OfferConfigDto $offer): array => [
                'offerCode' => $offer->offerCode,
                'productName' => $offer->productName,
                'projectType' => $offer->projectType->value,
                'nominalRateBps' => $offer->nominalRateBps,
                'aprBps' => $offer->aprBps,
                'insuranceRateBps' => $offer->insuranceRateBps,
                'minAmountCents' => $offer->criteria->minAmountCents,
                'maxAmountCents' => $offer->criteria->maxAmountCents,
                'minDurationMonths' => $offer->criteria->minDurationMonths,
                'maxDurationMonths' => $offer->criteria->maxDurationMonths,
                'representativeExample' => [
                    'amountCents' => $offer->referenceAmountCents,
                    'durationMonths' => $offer->referenceDurationMonths,
                    'monthlyInstallmentCents' => $offer->referenceMonthlyInstallmentCents,
                    'totalFinancingCostCents' => $offer->referenceTotalFinancingCostCents,
                ],
            ], $partner->offers),
        ], $this->catalog->getPartners());

        return $this->sendJson(['partners' => $partners], headers: ['Cache-Control' => 'public, max-age=300']);
    }
}
