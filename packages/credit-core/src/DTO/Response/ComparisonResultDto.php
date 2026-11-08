<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Response;

use Credit\Core\Enum\ScoringDecisionEnum;

/**
 * Résultat complet de la comparaison : décision, scoring, devis ou diagnostic.
 */
final readonly class ComparisonResultDto
{
    /**
     * @param list<QuoteOfferDto>    $quotes         triées par TAEG croissant (INSTANT_OFFERS), vide sinon
     * @param list<RuleExecutionDto> $ruleExecutions règles échouées par produit, au montant et à la durée demandés
     */
    public function __construct(
        public ScoringDecisionEnum $decision,
        public ScoringSummaryDto $scoringSummary,
        public array $quotes = [],
        public ?AdvisorFallbackDto $advisorFallback = null,
        public ?RejectionDto $rejection = null,
        public array $ruleExecutions = [],
    ) {}
}
