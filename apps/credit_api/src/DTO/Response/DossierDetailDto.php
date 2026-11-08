<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Enum\DossierWorkflowStatusEnum;
use Credit\Core\Enum\ScoringDecisionEnum;

/**
 * GET /api/v1/me/dossiers/{id} : date du résultat, récapitulatif, scoring, résultat figé et avancement.
 *
 * Les blocs JSON reprennent les DTO de credit-core tels qu'enregistrés à la date du résultat.
 */
final readonly class DossierDetailDto
{
    /**
     * @param array<string, mixed>        $request        les 8 étapes, sans les consentements
     * @param array<string, mixed>        $scoringSummary ScoringSummaryDto
     * @param list<array<string, mixed>>  $quotes         QuoteOfferDto[]
     * @param array<string, mixed>|null   $advisorFallback AdvisorFallbackDto
     * @param array<string, mixed>|null   $rejection      RejectionDto
     */
    public function __construct(
        public string $id,
        public string $submittedAt,
        public ScoringDecisionEnum $decision,
        public array $request,
        public array $scoringSummary,
        public array $quotes,
        public ?array $advisorFallback,
        public ?array $rejection,
        /** Consentement de rappel en vigueur (il peut avoir changé depuis le résultat). */
        public ?string $consentAdvisorCallbackAt,
        public string $phone,
        public DossierWorkflowStatusEnum $workflowStatus,
        /** Fin de validité des offres (résultat + 7 jours). */
        public string $quotesExpireAt,
        public bool $quotesExpired,
        public ?string $subscribedOfferCode,
        public ?string $subscribedAt,
    ) {}
}
