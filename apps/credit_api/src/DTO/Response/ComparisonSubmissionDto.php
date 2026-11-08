<?php

declare(strict_types=1);

namespace App\DTO\Response;

use Credit\Core\Enum\ClientStatusEnum;

/**
 * Réponse de POST /api/v1/compare : ni devis ni diagnostic, le résultat se lit dans l'espace client.
 */
final readonly class ComparisonSubmissionDto
{
    public function __construct(
        public ClientStatusEnum $clientStatus,
        public string $dossierId,
    ) {}
}
