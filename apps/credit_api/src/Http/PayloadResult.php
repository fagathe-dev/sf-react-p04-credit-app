<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Résultat de PayloadReader : le DTO valide, ou les violations champ => message.
 */
final readonly class PayloadResult
{
    /**
     * @param array<string, string> $violations
     */
    private function __construct(
        public ?object $dto,
        public array $violations,
    ) {}

    public static function valid(object $dto): self
    {
        return new self($dto, []);
    }

    /**
     * @param array<string, string> $violations
     */
    public static function invalid(array $violations): self
    {
        return new self(null, $violations);
    }

    public function isValid(): bool
    {
        return null !== $this->dto;
    }
}
