<?php

declare(strict_types=1);

namespace App\Notification\Handler;

use App\Enum\EmailNotificationTypeEnum;
use App\Notification\DossierMailDto;

/** Relance J+1 : offres non choisies 24 h après le résultat. */
final class QuoteReminderMailHandler extends AbstractDossierMailHandler
{
    public function getType(): EmailNotificationTypeEnum
    {
        return EmailNotificationTypeEnum::REMINDER_D1;
    }

    protected function getTemplate(): string
    {
        return 'emails/dossier/quote_reminder.html.twig';
    }

    protected function buildSubject(DossierMailDto $dto): string
    {
        return \sprintf('Vos %d offre%s de crédit vous attendent', $dto->quotesCount, $dto->quotesCount > 1 ? 's' : '');
    }
}
