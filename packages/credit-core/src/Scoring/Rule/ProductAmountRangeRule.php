<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\RuleContext;
use Credit\Core\Util\FrenchFormatter;

/**
 * Montant compris entre le minimum et le maximum du produit.
 * Devient STRUCTURAL quand le montant demandé est sous le minimum (le simulateur ne propose jamais d'emprunter plus).
 */
final class ProductAmountRangeRule extends AbstractProductRule
{
    public function getCode(): string
    {
        return 'PRODUCT_AMOUNT_RANGE';
    }

    public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::ADJUSTABLE;
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $amount = $context->requireSchedule()->amountCents;
        $criteria = $context->requireOffer()->criteria;

        if ($criteria->acceptsAmount($amount)) {
            return $this->pass($context);
        }

        return $this->fail($context, \sprintf(
            'Le montant de %s est hors des bornes de ce produit (%s à %s).',
            FrenchFormatter::cents($amount, false),
            FrenchFormatter::cents($criteria->minAmountCents, false),
            FrenchFormatter::cents($criteria->maxAmountCents, false),
        ));
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return 'Le montant demandé est inférieur au minimum proposé par nos partenaires pour ce projet.';
    }
}
