<?php

declare(strict_types=1);

namespace App\Notification\Handler;

use App\Notification\DossierMailDto;
use Fagathe\CorePhp\Mailer\AbstractMailerHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Address;

/**
 * Base des e-mails de dossier : destinataire = le client, contexte = DossierMailDto.
 *
 * @extends AbstractMailerHandler<DossierMailDto>
 */
abstract class AbstractDossierMailHandler extends AbstractMailerHandler implements DossierMailHandlerInterface
{
    public function __construct(
        #[Autowire(param: 'core_php.email.app_name')]
        protected readonly ?string $appName = null,
    ) {}

    abstract protected function buildSubject(DossierMailDto $dto): string;

    public function subjectFor(DossierMailDto $dto): string
    {
        return $this->buildSubject($dto);
    }

    protected function getSubject(object $dto): string
    {
        return $this->buildSubject($dto);
    }

    protected function getRecipients(object $dto): array
    {
        return [new Address($dto->recipientEmail, $dto->recipientName)];
    }

    protected function getContext(object $dto): array
    {
        return ['dto' => $dto];
    }
}
