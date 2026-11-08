<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\RuleContext;

/**
 * Durée comprise entre le minimum et le maximum du produit.
 */
final class ProductDurationRangeRule extends AbstractProductRule
{
    public function getCode(): string
    {
        return 'PRODUCT_DURATION_RANGE';
    }

    public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::ADJUSTABLE;
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $duration = $context->requireSchedule()->durationMonths;
        $criteria = $context->requireOffer()->criteria;

        if ($criteria->acceptsDuration($duration)) {
            return $this->pass($context);
        }

        return $this->fail($context, \sprintf(
            'La durée de %d mois est hors des bornes de ce produit (%d à %d mois).',
            $duration,
            $criteria->minDurationMonths,
            $criteria->maxDurationMonths,
        ));
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return 'La durée demandée dépasse la durée maximale proposée par nos partenaires pour ce projet.';
    }
}
