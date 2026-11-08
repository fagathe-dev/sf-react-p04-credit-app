<?php

declare(strict_types=1);

namespace App\Notification\Handler;

use App\Enum\EmailNotificationTypeEnum;
use App\Notification\DossierMailDto;

final class RequestConfirmationMailHandler extends AbstractDossierMailHandler
{
    public function getType(): EmailNotificationTypeEnum
    {
        return EmailNotificationTypeEnum::REQUEST_CONFIRMATION;
    }

    protected function getTemplate(): string
    {
        return 'emails/dossier/request_confirmation.html.twig';
    }

    protected function buildSubject(DossierMailDto $dto): string
    {
        return \sprintf('Votre demande de %s est enregistrée', mb_strtolower($dto->projectLabel));
    }
}
