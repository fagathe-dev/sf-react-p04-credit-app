<?php

declare(strict_types=1);

namespace App\Service;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\Enum\CreditProjectTypeEnum;

final readonly class ProjectPricing
{
    /**
     * @param list<string> $partners
     */
    public function __construct(
        public CreditProjectTypeEnum $project,
        public int $fromAprBps,
        public int $offersCount,
        public array $partners,
        public int $minAmountCents,
        public int $maxAmountCents,
        public int $minDurationMonths,
        public int $maxDurationMonths,
        /** Produit au plus petit TAEG : son exemple représentatif est recalculé (mensualité hors assurance). */
        public OfferConfigDto $exampleOffer,
        public int $exampleMonthlyInstallmentCents,
        public int $exampleTotalInterestCents,
        public int $exampleTotalRepaidCents,
    ) {}
}
