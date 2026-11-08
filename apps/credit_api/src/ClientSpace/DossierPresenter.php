<?php

declare(strict_types=1);

namespace App\ClientSpace;

use App\DTO\Response\DossierDetailDto;
use App\DTO\Response\DossierSummaryDto;
use App\Entity\Dossier;
use Credit\Core\Enum\CreditProjectTypeEnum;

/**
 * Convertit un Dossier en DTO de l'espace client (réutilisé par le back-office conseiller).
 */
final class DossierPresenter
{
    public function summary(Dossier $dossier): DossierSummaryDto
    {
        $request = $dossier->getRequestSnapshot();

        return new DossierSummaryDto(
            id: $dossier->getId()->toRfc4122(),
            submittedAt: $dossier->getSubmittedAt()->format(\DATE_ATOM),
            projectType: CreditProjectTypeEnum::from((string) ($request['projectType'] ?? CreditProjectTypeEnum::PERSONAL_GOODS->value)),
            requestedAmountCents: (int) ($request['requestedAmountCents'] ?? 0),
            durationMonths: (int) ($request['durationMonths'] ?? 0),
            decision: $dossier->getDecision(),
            quotesCount: \count($dossier->getQuotes()),
            workflowStatus: $dossier->getWorkflowStatus(),
            quotesExpireAt: $dossier->getQuotesExpireAt()->format(\DATE_ATOM),
        );
    }

    public function detail(Dossier $dossier, ?\DateTimeImmutable $now = null): DossierDetailDto
    {
        return new DossierDetailDto(
            id: $dossier->getId()->toRfc4122(),
            submittedAt: $dossier->getSubmittedAt()->format(\DATE_ATOM),
            decision: $dossier->getDecision(),
            request: $dossier->getRequestSnapshot(),
            scoringSummary: $dossier->getScoringSummary(),
            quotes: $dossier->getQuotes(),
            advisorFallback: $dossier->getAdvisorFallback(),
            rejection: $dossier->getRejection(),
            consentAdvisorCallbackAt: $dossier->getConsentAdvisorCallbackAt()?->format(\DATE_ATOM),
            phone: (string) ($dossier->getRequestSnapshot()['phone'] ?? $dossier->getClient()->getPhone()),
            workflowStatus: $dossier->getWorkflowStatus(),
            quotesExpireAt: $dossier->getQuotesExpireAt()->format(\DATE_ATOM),
            quotesExpired: $dossier->areQuotesExpired($now ?? new \DateTimeImmutable()),
            subscribedOfferCode: $dossier->getSubscribedOfferCode(),
            subscribedAt: $dossier->getSubscribedAt()?->format(\DATE_ATOM),
        );
    }
}
