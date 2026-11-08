<?php

declare(strict_types=1);

namespace App\Crm\Priority\Rule;

use App\Crm\Priority\CallbackPriorityRuleInterface;
use App\Crm\Priority\DossierMetrics;
use App\Enum\CallbackPriorityEnum;

/**
 * RG-04 — P3 : offres sans souscription générées il y a plus de 5 jours et pas encore expirées (7 jours).
 * Couplé à l'e-mail automatique « Votre devis expire bientôt » (EmailNotificationTypeEnum::EXPIRY_REMINDER).
 */
final readonly class LastReminderBeforeExpiryRule implements CallbackPriorityRuleInterface
{
    public const int MIN_AGE_EXCLUSIVE_SECONDS = 5 * self::DAY;

    public function getPriority(): CallbackPriorityEnum
    {
        return CallbackPriorityEnum::P3;
    }

    public function matches(DossierMetrics $metrics, \DateTimeImmutable $now): bool
    {
        return $metrics->isAwaitingOfferChoice()
            && $metrics->ageSeconds($now) > self::MIN_AGE_EXCLUSIVE_SECONDS
            && $now < $metrics->quotesExpireAt;
    }

    public function describe(DossierMetrics $metrics, \DateTimeImmutable $now): string
    {
        $hoursLeft = max(0, intdiv($metrics->quotesExpireAt->getTimestamp() - $now->getTimestamp(), self::HOUR));

        return \sprintf('Offres expirant dans %d h sans souscription : dernière relance, si un créneau est libre.', $hoursLeft);
    }
}
