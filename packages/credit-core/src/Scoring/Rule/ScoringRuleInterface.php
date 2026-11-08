<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Enum\RuleStageEnum;
use Credit\Core\Scoring\Model\RuleContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Règle de scoring. Ajouter une règle = ajouter une classe : ScoringEngine les découvre par tag.
 */
#[AutoconfigureTag(self::TAG)]
interface ScoringRuleInterface
{
    public const string TAG = 'credit_core.scoring_rule';

    public function getCode(): string;

    public function getStage(): RuleStageEnum;

    /** Sévérité déclarée : STRUCTURAL (non ajustable) ou ADJUSTABLE (levable par montant ou durée). */
    public function severity(): RuleSeverityEnum;

    public function evaluate(RuleContext $context): RuleExecutionDto;

    /**
     * Motif en langage clair pour un dossier non financable (RejectionDto).
     *
     * @param bool $failsForAllOffers la règle bloque tous les produits du projet
     */
    public function getRejectionReason(bool $failsForAllOffers): string;
}
