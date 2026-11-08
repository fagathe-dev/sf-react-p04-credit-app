<?php

declare(strict_types=1);

namespace App\DTO\Response;

final readonly class AdvisorProfileDto
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $lastLoginAt,
    ) {}
}
