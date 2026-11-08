<?php

declare(strict_types=1);

namespace App\Crm\Priority;

use App\Entity\Dossier;
use App\Enum\DossierWorkflowStatusEnum;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\ScoringDecisionEnum;

/**
 * Indicateurs d'un dossier utiles à la priorisation, lus dans son résultat figé (centimes, points de base).
 */
final readonly class DossierMetrics
{
    public function __construct(
        public \DateTimeImmutable $submittedAt,
        public \DateTimeImmutable $quotesExpireAt,
        public ScoringDecisionEnum $decision,
        public DossierWorkflowStatusEnum $workflowStatus,
        public CreditProjectTypeEnum $projectType,
        public int $requestedAmountCents,
        public int $durationMonths,
        public int $debtRatioAfterBps,
        public int $remainderToLiveCents,
        public int $weightedIncomeCents,
        public ?string $referenceOfferCode,
    ) {}

    public static function fromDossier(Dossier $dossier): self
    {
        $request = $dossier->getRequestSnapshot();
        $summary = $dossier->getScoringSummary();

        return new self(
            submittedAt: $dossier->getSubmittedAt(),
            quotesExpireAt: $dossier->getQuotesExpireAt(),
            decision: $dossier->getDecision(),
            workflowStatus: $dossier->getWorkflowStatus(),
            projectType: CreditProjectTypeEnum::tryFrom((string) ($request['projectType'] ?? '')) ?? CreditProjectTypeEnum::PERSONAL_GOODS,
            requestedAmountCents: (int) ($request['requestedAmountCents'] ?? 0),
            durationMonths: (int) ($request['durationMonths'] ?? 0),
            debtRatioAfterBps: (int) ($summary['debtRatioAfterBps'] ?? 0),
            remainderToLiveCents: (int) ($summary['remainderToLiveCents'] ?? 0),
            weightedIncomeCents: (int) ($summary['weightedIncomeCents'] ?? 0),
            referenceOfferCode: isset($summary['referenceOfferCode']) ? (string) $summary['referenceOfferCode'] : null,
        );
    }

    /** Ancienneté du résultat, en secondes. */
    public function ageSeconds(\DateTimeImmutable $now): int
    {
        return $now->getTimestamp() - $this->submittedAt->getTimestamp();
    }

    public function isAwaitingOfferChoice(): bool
    {
        return DossierWorkflowStatusEnum::QUOTES_GENERATED === $this->workflowStatus;
    }
}
