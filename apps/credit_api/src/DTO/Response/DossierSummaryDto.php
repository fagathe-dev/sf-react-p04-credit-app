<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Enum\DossierWorkflowStatusEnum;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\ScoringDecisionEnum;

/**
 * Ligne de la liste des dossiers (GET /api/v1/me/dossiers).
 */
final readonly class DossierSummaryDto
{
    public function __construct(
        public string $id,
        public string $submittedAt,
        public CreditProjectTypeEnum $projectType,
        public int $requestedAmountCents,
        public int $durationMonths,
        public ScoringDecisionEnum $decision,
        public int $quotesCount,
        public DossierWorkflowStatusEnum $workflowStatus,
        public string $quotesExpireAt,
    ) {}
}
