<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Response;

use Credit\Core\Enum\AdjustmentKindEnum;

/**
 * Piste chiffrée et indicative : c'est le conseiller qui la valide avec l'Applicant.
 */
final readonly class AdjustmentSuggestionDto
{
    public function __construct(
        public AdjustmentKindEnum $kind,
        public string $label,
        public string $partnerCode,
        public string $partnerName,
        public string $partnerLogo,
        public string $offerCode,
        public string $productName,
        public int $amountCents,
        public int $durationMonths,
        public int $monthlyInstallmentCents,
        public int $aprBps,
        public int $debtRatioAfterBps,
        /** Éloignement par rapport à la demande, en points de base (baisse relative du montant + hausse relative de la durée). */
        public int $distanceBps,
    ) {}
}
