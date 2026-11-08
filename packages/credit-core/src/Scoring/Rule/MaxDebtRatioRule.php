<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\RuleContext;
use Credit\Core\Util\FrenchFormatter;

/**
 * Plafond d'endettement projeté (charges existantes + nouvelle mensualité) du produit.
 */
final class MaxDebtRatioRule extends AbstractProductRule
{
    public function getCode(): string
    {
        return 'MAX_DEBT_RATIO';
    }

    public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::ADJUSTABLE;
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $debtRatio = (int) $context->debtRatioAfterBps;
        $maximum = $context->requireOffer()->criteria->maxDebtRatioBps;

        if ($debtRatio <= $maximum) {
            return $this->pass($context);
        }

        return $this->fail($context, \sprintf(
            'Votre taux d\'endettement projeté est de %s alors que le plafond automatique est fixé à %s.',
            FrenchFormatter::bps($debtRatio),
            FrenchFormatter::bps($maximum),
        ));
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return 'Votre taux d\'endettement actuel ne permet pas d\'ajouter une nouvelle mensualité, même réduite au minimum.';
    }
}
