<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * PATCH /api/v1/me/consents : donne ou retire le consentement de rappel par un conseiller.
 */
final readonly class ConsentUpdateDto
{
    public function __construct(
        #[Assert\NotNull(message: 'Le consentement de rappel est obligatoire.')]
        public ?bool $consentAdvisorCallback = null,
    ) {}
}
