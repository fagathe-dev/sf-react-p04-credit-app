<?php

declare(strict_types=1);

namespace App\Notification;

use App\Entity\Dossier;
use App\Enum\EmailNotificationStatusEnum;
use App\Enum\EmailNotificationTypeEnum;
use App\Repository\DossierRepository;

/**
 * Relances e-mail des offres non choisies (dossiers actifs QUOTES_GENERATED, offres non expirées) :
 * - J+1 : de 24 h à 5 jours après le résultat ;
 * - J+7 « Votre devis expire bientôt » (RG-04) : après 5 jours et avant l'expiration (7 jours).
 *
 * Idempotent : DossierNotifier n'envoie jamais deux fois le même type pour un dossier.
 */
final readonly class QuoteReminderScheduler
{
    public const int D1_AFTER_SECONDS = 86_400;
    public const int EXPIRY_AFTER_SECONDS = 5 * 86_400;

    public function __construct(
        private DossierRepository $dossierRepository,
        private DossierNotifier $notifier,
    ) {}

    /**
     * @return array{sent: int, failed: int}
     */
    public function run(\DateTimeImmutable $now): array
    {
        $since = $now->modify(\sprintf('-%d days', Dossier::QUOTE_VALIDITY_DAYS));
        $until = $now->modify(\sprintf('-%d seconds', self::D1_AFTER_SECONDS));
        $counts = ['sent' => 0, 'failed' => 0];

        foreach ($this->dossierRepository->findAwaitingOfferChoice($since, $until) as $dossier) {
            $type = $this->dueReminder($dossier, $now);
            if (null === $type) {
                continue;
            }

            $notification = $this->notifier->notify($dossier, $type, $now);
            if (null !== $notification) {
                ++$counts[EmailNotificationStatusEnum::SENT === $notification->getStatus() ? 'sent' : 'failed'];
            }
        }

        return $counts;
    }

    private function dueReminder(Dossier $dossier, \DateTimeImmutable $now): ?EmailNotificationTypeEnum
    {
        if ($dossier->areQuotesExpired($now)) {
            return null;
        }

        $age = $now->getTimestamp() - $dossier->getSubmittedAt()->getTimestamp();

        return match (true) {
            $age > self::EXPIRY_AFTER_SECONDS => EmailNotificationTypeEnum::EXPIRY_REMINDER,
            $age >= self::D1_AFTER_SECONDS => EmailNotificationTypeEnum::REMINDER_D1,
            default => null,
        };
    }
}
