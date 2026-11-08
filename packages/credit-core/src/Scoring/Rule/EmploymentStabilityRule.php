<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\RuleContext;

/**
 * Ancienneté professionnelle minimale exigée par le produit.
 */
final class EmploymentStabilityRule extends AbstractProductRule
{
    public function getCode(): string
    {
        return 'EMPLOYMENT_STABILITY';
    }

    public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::STRUCTURAL;
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $seniority = $context->requireProfile()->seniorityMonths;
        $minimum = $context->requireOffer()->criteria->minSeniorityMonths;

        if ($seniority >= $minimum) {
            return $this->pass($context);
        }

        return $this->fail($context, \sprintf('Votre ancienneté professionnelle (%d mois) est inférieure au minimum exigé (%d mois).', $seniority, $minimum));
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return 'Votre ancienneté professionnelle est inférieure au minimum exigé par nos partenaires.';
    }
}
