<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Enum\CallbackPriorityEnum;
use App\Enum\DossierWorkflowStatusEnum;
use Credit\Core\Enum\CallbackStatusEnum;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\ScoringDecisionEnum;

/**
 * Ligne de la file de rappels (GET /api/v1/crm/callbacks).
 */
final readonly class CrmCallbackItemDto
{
    public function __construct(
        public string $dossierId,
        public string $submittedAt,
        public string $clientName,
        public string $email,
        public string $phone,
        public CreditProjectTypeEnum $projectType,
        public int $requestedAmountCents,
        public int $durationMonths,
        public ScoringDecisionEnum $decision,
        public ?CallbackStatusEnum $callbackStatus,
        public ?string $callbackUpdatedAt,
        public ?string $advisorName,
        public CallbackPriorityEnum $priority,
        public string $priorityReason,
        public DossierWorkflowStatusEnum $workflowStatus,
        public int $debtRatioAfterBps,
    ) {}
}
