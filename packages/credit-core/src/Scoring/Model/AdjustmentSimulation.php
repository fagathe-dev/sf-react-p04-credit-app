<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Model;

use Credit\Core\DTO\Response\AdjustmentSuggestionDto;
use Credit\Core\DTO\Response\RuleExecutionDto;

/**
 * Résultat de FinancingAdjustmentSimulator.
 */
final readonly class AdjustmentSimulation
{
    /**
     * @param list<AdjustmentSuggestionDto>               $suggestions        jusqu'à 3, de la moins à la plus éloignée de la demande
     * @param list<RuleExecutionDto>                      $requestFailures    échecs au montant et à la durée demandés, sévérité effective
     * @param array<string, list<RuleExecutionDto>>       $structuralFailures par code d'offre : échecs dans la meilleure variante du produit
     * @param list<string>                                $reasons            raisons chiffrées du blocage (produit de la piste la plus proche)
     */
    public function __construct(
        public array $suggestions,
        public array $requestFailures,
        public array $structuralFailures,
        public array $reasons,
    ) {}

    public function isFinanceable(): bool
    {
        return [] !== $this->suggestions;
    }
}
