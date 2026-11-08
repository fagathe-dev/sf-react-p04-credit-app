<?php

declare(strict_types=1);

namespace App\Notification\Handler;

use App\Enum\EmailNotificationTypeEnum;
use App\Notification\DossierMailDto;

/** Relance J+7 « Votre devis expire bientôt » (RG-04), envoyée entre J+5 et l'expiration. */
final class QuoteExpiryMailHandler extends AbstractDossierMailHandler
{
    public function getType(): EmailNotificationTypeEnum
    {
        return EmailNotificationTypeEnum::EXPIRY_REMINDER;
    }

    protected function getTemplate(): string
    {
        return 'emails/dossier/quote_expiry.html.twig';
    }

    protected function buildSubject(DossierMailDto $dto): string
    {
        return 'Votre devis expire bientôt';
    }
}
