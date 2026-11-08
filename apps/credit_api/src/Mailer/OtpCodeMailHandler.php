<?php

declare(strict_types=1);

namespace App\Mailer;

use Fagathe\CorePhp\Mailer\AbstractMailerHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Address;

/**
 * Code de connexion envoyé aux clients et aux conseillers.
 *
 * @extends AbstractMailerHandler<OtpCodeMailDto>
 */
final class OtpCodeMailHandler extends AbstractMailerHandler
{
    public function __construct(
        #[Autowire(param: 'core_php.email.app_name')]
        private readonly ?string $appName = null,
    ) {}

    protected function getTemplate(): string
    {
        return 'emails/otp_code.html.twig';
    }

    protected function getSubject(object $dto): string
    {
        return \sprintf('Votre code de connexion %s', $this->appName ?: '');
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
