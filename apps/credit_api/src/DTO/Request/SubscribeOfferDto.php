<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * POST /api/v1/me/dossiers/{id}/subscribe : le client choisit une offre de son dossier.
 */
final readonly class SubscribeOfferDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Choisissez une offre.')]
        #[Assert\Length(max: 50)]
        public string $offerCode = '',
    ) {}
}
