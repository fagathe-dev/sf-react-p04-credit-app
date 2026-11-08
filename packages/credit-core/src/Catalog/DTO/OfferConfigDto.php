<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\DTO;

use Credit\Core\Enum\CreditProjectTypeEnum;

/**
 * Offre d'un partenaire et ses critères (format JSON : section 4.5 de la spec).
 *
 * L'exemple représentatif (reference*) ne sert qu'à l'affichage : les devis sont toujours
 * recalculés par LoanMathCalculator à partir du montant et de la durée de la demande.
 */
final readonly class OfferConfigDto
{
    public function __construct(
        public string $partnerCode,
        public string $partnerName,
        public string $partnerLogo,
        public string $offerCode,
        public string $productName,
        public CreditProjectTypeEnum $projectType,
        public int $nominalRateBps,
        public int $aprBps,
        public int $insuranceRateBps,
        public int $referenceAmountCents,
        public int $referenceDurationMonths,
        public int $referenceMonthlyInstallmentCents,
        public int $referenceTotalFinancingCostCents,
        public OfferCriteriaDto $criteria,
    ) {}
}
