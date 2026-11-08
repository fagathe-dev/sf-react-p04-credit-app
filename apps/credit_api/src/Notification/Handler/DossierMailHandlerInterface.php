<?php

declare(strict_types=1);

namespace App\Notification\Handler;

use App\Enum\EmailNotificationTypeEnum;
use App\Notification\DossierMailDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Un e-mail de dossier. Ajouter un e-mail = un cas d'EmailNotificationTypeEnum + un handler + un gabarit.
 */
#[AutoconfigureTag(self::TAG)]
interface DossierMailHandlerInterface
{
    public const string TAG = 'app.dossier_mail_handler';

    public function getType(): EmailNotificationTypeEnum;

    public function subjectFor(DossierMailDto $dto): string;

    /**
     * @throws \Throwable envoi impossible (MailerExceptionInterface de core-php, transport…)
     */
    public function send(object $dto): void;
}
