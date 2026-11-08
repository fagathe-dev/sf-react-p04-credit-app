<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\RuleContext;

/**
 * Grade de risque minimal du produit. Ajustable : l'endettement projeté entre dans le score.
 */
final class MinRiskGradeRule extends AbstractProductRule
{
    public function getCode(): string
    {
        return 'MIN_RISK_GRADE';
    }

    public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::ADJUSTABLE;
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $grade = $context->requireScore()->grade;
        $minimum = $context->requireOffer()->criteria->minRiskGrade;

        if ($grade->isAtLeast($minimum)) {
            return $this->pass($context);
        }

        return $this->fail($context, \sprintf(
            'Votre profil obtient le grade %s (score %d/1000) alors que ce produit exige au minimum le grade %s.',
            $grade->letter(),
            $context->requireScore()->score,
            $minimum->letter(),
        ));
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return 'Votre profil de risque ne correspond aux critères d\'aucune offre de ce projet, même avec la plus petite mensualité possible.';
    }
}
