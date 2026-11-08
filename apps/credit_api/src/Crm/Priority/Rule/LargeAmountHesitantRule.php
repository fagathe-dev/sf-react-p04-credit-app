<?php

declare(strict_types=1);

namespace App\Crm\Priority\Rule;

use App\Crm\Priority\CallbackPriorityRuleInterface;
use App\Crm\Priority\DossierMetrics;
use App\Enum\CallbackPriorityEnum;
use Credit\Core\Util\FrenchFormatter;

/**
 * RG-02 — P1 bis : gros montant éligible (≥ 15 000 €, endettement ≤ 33 %) non souscrit entre 2 h et 36 h.
 */
final readonly class LargeAmountHesitantRule implements CallbackPriorityRuleInterface
{
    public const int MIN_AGE_SECONDS = 2 * self::HOUR;
    public const int MAX_AGE_SECONDS = 36 * self::HOUR;
    public const int MIN_AMOUNT_CENTS = 1_500_000;
    public const int MAX_DEBT_RATIO_BPS = 3_300;

    public function getPriority(): CallbackPriorityEnum
    {
        return CallbackPriorityEnum::P1_BIS;
    }

    public function matches(DossierMetrics $metrics, \DateTimeImmutable $now): bool
    {
        $age = $metrics->ageSeconds($now);

        return $metrics->isAwaitingOfferChoice()
            && $metrics->debtRatioAfterBps <= self::MAX_DEBT_RATIO_BPS
            && $metrics->requestedAmountCents >= self::MIN_AMOUNT_CENTS
            && $age >= self::MIN_AGE_SECONDS && $age <= self::MAX_AGE_SECONDS;
    }

    public function describe(DossierMetrics $metrics, \DateTimeImmutable $now): string
    {
        return \sprintf(
            'Projet de %s éligible, aucune offre choisie depuis %d h : appel de réassurance pour lever les derniers freins.',
            FrenchFormatter::cents($metrics->requestedAmountCents, false),
            intdiv($metrics->ageSeconds($now), self::HOUR),
        );
    }
}
