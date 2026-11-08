<?php

declare(strict_types=1);

namespace App\Notification\Handler;

use App\Enum\EmailNotificationTypeEnum;
use App\Notification\DossierMailDto;

final class PartnerTransmissionMailHandler extends AbstractDossierMailHandler
{
    public function getType(): EmailNotificationTypeEnum
    {
        return EmailNotificationTypeEnum::PARTNER_TRANSMISSION;
    }

    protected function getTemplate(): string
    {
        return 'emails/dossier/partner_transmission.html.twig';
    }

    protected function buildSubject(DossierMailDto $dto): string
    {
        return 'Votre demande a été transmise à nos établissements partenaires';
    }
}
