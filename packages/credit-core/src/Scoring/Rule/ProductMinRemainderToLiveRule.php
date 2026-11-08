<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\RuleContext;
use Credit\Core\Util\FrenchFormatter;

/**
 * Reste à vivre minimal exigé par le produit (minRemainderToLive).
 * Combinée à RemainderToLiveRule, le seuil appliqué est le plus élevé des deux.
 */
final class ProductMinRemainderToLiveRule extends AbstractProductRule
{
    public function getCode(): string
    {
        return 'PRODUCT_MIN_REMAINDER_TO_LIVE';
    }

    public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::ADJUSTABLE;
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $remainder = (int) $context->remainderToLiveCents;
        $minimum = $context->requireOffer()->criteria->minRemainderToLiveCents;

        if ($remainder >= $minimum) {
            return $this->pass($context);
        }

        return $this->fail($context, \sprintf(
            'Votre reste à vivre après mensualité serait de %s, pour un minimum de %s exigé par ce partenaire.',
            FrenchFormatter::cents($remainder),
            FrenchFormatter::cents($minimum, false),
        ));
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return 'Votre reste à vivre serait inférieur au minimum exigé par nos partenaires, même avec la plus petite mensualité possible.';
    }
}
