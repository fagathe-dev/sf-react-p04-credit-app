<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Enum\DossierWorkflowStatusEnum;
use Credit\Core\Enum\CallbackStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PATCH /api/v1/crm/dossiers/{id}/callback.
 */
final readonly class CallbackFollowUpDto
{
    public function __construct(
        #[Assert\NotNull(message: 'Le statut d\'appel est obligatoire.')]
        public ?CallbackStatusEnum $callbackStatus = null,
        #[Assert\Length(max: 5000, maxMessage: 'La note ne peut pas dépasser {{ limit }} caractères.')]
        public ?string $advisorNote = null,
        /** Validation (APPROVED) ou refus (REJECTED) d'une offre choisie : facultatif. */
        public ?DossierWorkflowStatusEnum $workflowStatus = null,
    ) {}
}
