<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Enum\CallbackPriorityEnum;
use App\Enum\DossierWorkflowStatusEnum;
use Credit\Core\Enum\CallbackStatusEnum;

/**
 * GET /api/v1/crm/dossiers/{id} : DossierDetailDto + coordonnées, priorité, suivi d'appel, e-mails et acquisition.
 */
final readonly class CrmDossierDetailDto
{
    /**
     * @param array<string, string|null>|null $acquisition
     * @param list<DossierWorkflowStatusEnum> $workflowTransitions statuts que le conseiller peut appliquer
     * @param list<EmailNotificationDto>      $emails              historique des e-mails transactionnels
     */
    public function __construct(
        public DossierDetailDto $dossier,
        public string $clientName,
        public string $email,
        public string $phone,
        public ?CallbackStatusEnum $callbackStatus,
        public ?string $advisorNote,
        public ?string $callbackUpdatedAt,
        public ?string $advisorName,
        public ?array $acquisition,
        public CallbackPriorityEnum $priority,
        public string $priorityReason,
        public array $workflowTransitions,
        public array $emails,
    ) {}
}
