<?php

declare(strict_types=1);

namespace App\Service;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\Catalog\InMemoryProductCatalog;

/**
 * Barème transmis au simulateur rapide (assets/ts/components/quickSimulator.ts) :
 * bornes et taux de chaque produit, en centimes et points de base. Aucune donnée personnelle.
 */
final readonly class SimulatorDataProvider
{
    public function __construct(
        private InMemoryProductCatalog $catalog,
    ) {}

    /**
     * @return list<array{projectType: string, offerCode: string, productName: string, partnerName: string, nominalRateBps: int, aprBps: int, minAmountCents: int, maxAmountCents: int, minDurationMonths: int, maxDurationMonths: int}>
     */
    public function offers(): array
    {
        return array_map(static fn (OfferConfigDto $offer): array => [
            'projectType' => $offer->projectType->value,
            'offerCode' => $offer->offerCode,
            'productName' => $offer->productName,
            'partnerName' => $offer->partnerName,
            'nominalRateBps' => $offer->nominalRateBps,
            'aprBps' => $offer->aprBps,
            'minAmountCents' => $offer->criteria->minAmountCents,
            'maxAmountCents' => $offer->criteria->maxAmountCents,
            'minDurationMonths' => $offer->criteria->minDurationMonths,
            'maxDurationMonths' => $offer->criteria->maxDurationMonths,
        ], $this->catalog->getOffers());
    }
}
