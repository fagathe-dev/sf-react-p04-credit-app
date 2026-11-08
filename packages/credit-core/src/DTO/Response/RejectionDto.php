<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Response;

/**
 * Dossier non financable : motifs structurels en langage clair, sans promesse de rappel.
 */
final readonly class RejectionDto
{
    /**
     * @param list<string> $reasons
     */
    public function __construct(
        public array $reasons,
    ) {}
}
