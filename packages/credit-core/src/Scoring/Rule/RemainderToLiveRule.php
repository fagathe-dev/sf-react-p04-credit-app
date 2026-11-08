<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\RuleContext;
use Credit\Core\Util\FrenchFormatter;

/**
 * Reste à vivre après mensualité, au moins égal au seuil du foyer (adultes et enfants à charge).
 */
final class RemainderToLiveRule extends AbstractProductRule
{
    public function getCode(): string
    {
        return 'REMAINDER_TO_LIVE';
    }

    public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::ADJUSTABLE;
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $remainder = (int) $context->remainderToLiveCents;
        $minimum = $context->requireProfile()->householdMinRemainderCents;

        if ($remainder >= $minimum) {
            return $this->pass($context);
        }

        return $this->fail($context, \sprintf(
            'Votre reste à vivre après mensualité serait de %s, pour un minimum de %s pour votre foyer.',
            FrenchFormatter::cents($remainder),
            FrenchFormatter::cents($minimum, false),
        ));
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return 'Votre reste à vivre serait insuffisant, même avec la plus petite mensualité possible.';
    }
}
