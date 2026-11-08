<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class OtpVerifyDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'adresse e-mail est obligatoire.')]
        #[Assert\Email(message: 'L\'adresse e-mail est invalide.')]
        public string $email = '',
        #[Assert\NotBlank(message: 'Le code est obligatoire.')]
        #[Assert\Regex(pattern: '/^\d{6}$/', message: 'Le code comporte 6 chiffres.')]
        public string $code = '',
    ) {}
}
