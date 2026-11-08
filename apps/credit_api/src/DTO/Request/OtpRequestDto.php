<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * POST /api/v1/auth/otp/request et /api/v1/crm/auth/otp/request.
 */
final readonly class OtpRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'adresse e-mail est obligatoire.')]
        #[Assert\Email(message: 'L\'adresse e-mail est invalide.')]
        public string $email = '',
        /** Dossier en attente à rattacher (renvoi du code depuis /code?dossierId=…). */
        #[Assert\Uuid(message: 'Identifiant de dossier invalide.')]
        public ?string $dossierId = null,
    ) {}
}
