<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Response;

use Credit\Core\Enum\RiskGradeEnum;

/**
 * Résumé du scoring, calculé pour le montant et la durée demandés.
 *
 * La mensualité de référence est celle de la meilleure offre (INSTANT_OFFERS)
 * ou, à défaut, celle du produit au plus petit TAEG du projet.
 */
final readonly class ScoringSummaryDto
{
    /**
     * @param list<ScoreContributionDto> $scoreBreakdown
     */
    public function __construct(
        public int $score,
        public RiskGradeEnum $riskGrade,
        public int $weightedIncomeCents,
        public int $existingChargesCents,
        public int $debtRatioBeforeBps,
        public int $debtRatioAfterBps,
        public int $referenceMonthlyInstallmentCents,
        public int $remainderToLiveCents,
        public int $minRemainderToLiveCents,
        public ?string $referenceOfferCode,
        public array $scoreBreakdown = [],
    ) {}
}
