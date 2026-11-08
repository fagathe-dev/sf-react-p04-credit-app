<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\RuleContext;
use Credit\Core\Util\FrenchFormatter;

/**
 * Revenu mensuel minimal du produit, comparé aux revenus pondérés du foyer.
 */
final class MinMonthlyIncomeRule extends AbstractProductRule
{
    public function getCode(): string
    {
        return 'MIN_MONTHLY_INCOME';
    }

    public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::STRUCTURAL;
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $income = $context->requireProfile()->weightedIncomeCents;
        $minimum = $context->requireOffer()->criteria->minMonthlyIncomeCents;

        if ($income >= $minimum) {
            return $this->pass($context);
        }

        return $this->fail($context, \sprintf(
            'Vos revenus mensuels retenus (%s) sont inférieurs au minimum exigé par ce produit (%s).',
            FrenchFormatter::cents($income),
            FrenchFormatter::cents($minimum, false),
        ));
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return 'Vos revenus sont inférieurs au minimum exigé par nos partenaires pour ce projet.';
    }
}
