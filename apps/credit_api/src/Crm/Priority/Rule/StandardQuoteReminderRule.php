<?php

declare(strict_types=1);

namespace App\Crm\Priority\Rule;

use App\Crm\Priority\CallbackPriorityRuleInterface;
use App\Crm\Priority\DossierMetrics;
use App\Enum\CallbackPriorityEnum;
use Credit\Core\Util\FrenchFormatter;

/**
 * RG-03 — P2 : devis standard éligible (< 15 000 €, endettement ≤ 33 %) non souscrit entre 24 h et 48 h.
 */
final readonly class StandardQuoteReminderRule implements CallbackPriorityRuleInterface
{
    public const int MIN_AGE_SECONDS = 24 * self::HOUR;
    public const int MAX_AGE_SECONDS = 48 * self::HOUR;
    public const int MAX_AMOUNT_EXCLUSIVE_CENTS = 1_500_000;
    public const int MAX_DEBT_RATIO_BPS = 3_300;

    public function getPriority(): CallbackPriorityEnum
    {
        return CallbackPriorityEnum::P2;
    }

    public function matches(DossierMetrics $metrics, \DateTimeImmutable $now): bool
    {
        $age = $metrics->ageSeconds($now);

        return $metrics->isAwaitingOfferChoice()
            && $metrics->debtRatioAfterBps <= self::MAX_DEBT_RATIO_BPS
            && $metrics->requestedAmountCents < self::MAX_AMOUNT_EXCLUSIVE_CENTS
            && $age >= self::MIN_AGE_SECONDS && $age <= self::MAX_AGE_SECONDS;
    }

    public function describe(DossierMetrics $metrics, \DateTimeImmutable $now): string
    {
        return \sprintf(
            'Devis de %s sans offre choisie depuis %d h : comprendre l\'hésitation, ajuster la mensualité ou aider au choix.',
            FrenchFormatter::cents($metrics->requestedAmountCents, false),
            intdiv($metrics->ageSeconds($now), self::HOUR),
        );
    }
}
