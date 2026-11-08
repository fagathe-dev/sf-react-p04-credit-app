<?php

declare(strict_types=1);

namespace App\DTO\Response;

use Credit\Core\Enum\CivilityEnum;

/**
 * GET /api/v1/me : informations et état des consentements du client connecté.
 */
final readonly class ClientProfileDto
{
    public function __construct(
        public string $id,
        public string $email,
        public CivilityEnum $civility,
        public string $firstName,
        public string $lastName,
        public string $birthDate,
        public string $phone,
        public string $createdAt,
        public ?string $lastLoginAt,
        public ConsentStateDto $consents,
    ) {}
}
