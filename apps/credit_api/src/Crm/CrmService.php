<?php

declare(strict_types=1);

namespace App\Crm;

use App\ClientSpace\DossierPresenter;
use App\Crm\Priority\CallbackPriorityResolver;
use App\Crm\Priority\PriorityAssessment;
use App\DTO\Request\CallbackFollowUpDto;
use App\DTO\Response\AdvisorProfileDto;
use App\DTO\Response\CrmCallbackItemDto;
use App\DTO\Response\CrmDossierDetailDto;
use App\DTO\Response\EmailNotificationDto;
use App\DTO\Response\PaginatedResponseDto;
use App\Entity\Advisor;
use App\Entity\Dossier;
use App\Entity\EmailNotification;
use App\Enum\CallbackPriorityEnum;
use App\Enum\DossierWorkflowStatusEnum;
use App\Http\ApiResponseTrait;
use App\Http\PayloadReader;
use App\Repository\DossierRepository;
use App\Repository\EmailNotificationRepository;
use Credit\Core\Enum\CallbackStatusEnum;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\ScoringDecisionEnum;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

/**
 * Back-office conseillers (/api/v1/crm) : seuls les dossiers ACTIVE avec consentement de rappel sont accessibles.
 *
 * File de rappels : priorité calculée (RG-01 à RG-04), triée P1, P1 bis, P2, P3, puis les plus anciens d'abord.
 * Filtre `priority` : absent = file d'appels (P1 à P3) ; P1 | P1_BIS | P2 | P3 | NONE ; ALL = tous les dossiers.
 */
final class CrmService
{
    use ApiResponseTrait;
    use LoggerTrait;

    public const int DEFAULT_LIMIT = 20;
    public const int MAX_LIMIT = 100;
    public const string PRIORITY_ALL = 'ALL';

    public function __construct(
        private readonly DossierRepository $dossierRepository,
        private readonly EmailNotificationRepository $notificationRepository,
        private readonly DossierPresenter $presenter,
        private readonly PayloadReader $payloadReader,
        private readonly CallbackPriorityResolver $priorityResolver,
    ) {}

    public function getProfile(Advisor $advisor): object
    {
        return $this->sendJson(new AdvisorProfileDto(
            id: $advisor->getId()->toRfc4122(),
            email: $advisor->getEmail(),
            firstName: $advisor->getFirstName(),
            lastName: $advisor->getLastName(),
            lastLoginAt: $advisor->getLastLoginAt()?->format(\DATE_ATOM),
        ));
    }

    public function listCallbacks(Request $request): object
    {
        $query = $request->query;
        $now = new \DateTimeImmutable();
        $priorityFilter = (string) $query->get('priority', '');
        $priority = CallbackPriorityEnum::tryFrom($priorityFilter);
        $queueOnly = '' === $priorityFilter;
        $page = max(1, $query->getInt('page', 1));
        $limit = min(self::MAX_LIMIT, max(1, $query->getInt('limit', self::DEFAULT_LIMIT)));

        // Les règles P1 à P3 portent sur 7 jours au plus : inutile de lire les dossiers plus anciens
        $since = $queueOnly || (null !== $priority && CallbackPriorityEnum::NONE !== $priority)
            ? $now->modify(\sprintf('-%d days', Dossier::QUOTE_VALIDITY_DAYS))
            : null;

        $candidates = $this->dossierRepository->findCallbackCandidates(
            ScoringDecisionEnum::tryFrom((string) $query->get('decision', '')),
            CallbackStatusEnum::tryFrom((string) $query->get('callbackStatus', '')),
            DossierWorkflowStatusEnum::tryFrom((string) $query->get('workflowStatus', '')),
            $since,
        );

        $rows = [];
        foreach ($candidates as $dossier) {
            $assessment = $this->priorityResolver->assess($dossier, $now);
            $keep = match (true) {
                $queueOnly => CallbackPriorityEnum::NONE !== $assessment->priority,
                null !== $priority => $priority === $assessment->priority,
                default => true, // ALL ou valeur inconnue
            };
            if ($keep) {
                $rows[] = [$dossier, $assessment];
            }
        }

        usort($rows, static fn (array $a, array $b): int => [$a[1]->priority->rank(), $a[0]->getSubmittedAt()] <=> [$b[1]->priority->rank(), $b[0]->getSubmittedAt()]);

        $total = \count($rows);
        $items = array_map(
            fn (array $row): CrmCallbackItemDto => $this->callbackItem($row[0], $row[1]),
            \array_slice($rows, ($page - 1) * $limit, $limit),
        );

        return $this->sendJson(new PaginatedResponseDto(
            items: $items,
            page: $page,
            limit: $limit,
            total: $total,
            pages: max(1, (int) ceil($total / $limit)),
        ));
    }

    public function getDossier(string $id): object
    {
        $dossier = $this->findDossier($id);

        return null === $dossier ? $this->sendNotFound('Dossier introuvable.') : $this->sendJson($this->detail($dossier));
    }

    public function updateCallback(Advisor $advisor, string $id, Request $request): object
    {
        $dossier = $this->findDossier($id);
        if (null === $dossier) {
            return $this->sendNotFound('Dossier introuvable.');
        }

        $payload = $this->payloadReader->read($request, CallbackFollowUpDto::class);
        if (!$payload->isValid()) {
            return $this->sendUnprocessable($payload->violations);
        }

        /** @var CallbackFollowUpDto $dto */
        $dto = $payload->dto;
        $now = new \DateTimeImmutable();

        if (null !== $dto->workflowStatus && $dto->workflowStatus !== $dossier->getWorkflowStatus()) {
            if (!$dossier->canAdvisorMoveTo($dto->workflowStatus)) {
                return $this->sendUnprocessable(['workflowStatus' => \sprintf(
                    'Le dossier « %s » ne peut pas passer à « %s ».',
                    $dossier->getWorkflowStatus()->label(),
                    $dto->workflowStatus->label(),
                )]);
            }
            $dossier->moveWorkflowTo($dto->workflowStatus, $now);
        }

        $note = null === $dto->advisorNote || '' === trim($dto->advisorNote) ? null : trim($dto->advisorNote);
        $dossier->recordCallbackFollowUp($advisor, $dto->callbackStatus ?? CallbackStatusEnum::TO_CALL, $note, $now);
        $this->dossierRepository->save($dossier);

        $this->generateLog(LoggerLevelEnum::Info, [
            'message' => 'Suivi d\'appel mis à jour.',
            'data' => [
                'dossier' => $dossier->getId()->toRfc4122(),
                'advisor' => $advisor->getId()->toRfc4122(),
                'callbackStatus' => $dossier->getCallbackStatus()?->value,
                'workflowStatus' => $dossier->getWorkflowStatus()->value,
            ],
        ], ['action' => 'crm.callback_updated']);

        return $this->sendJson($this->detail($dossier));
    }

    private function findDossier(string $id): ?Dossier
    {
        return Uuid::isValid($id) ? $this->dossierRepository->findCallbackDossier(Uuid::fromString($id)) : null;
    }

    private function callbackItem(Dossier $dossier, PriorityAssessment $assessment): CrmCallbackItemDto
    {
        $request = $dossier->getRequestSnapshot();
        $client = $dossier->getClient();

        return new CrmCallbackItemDto(
            dossierId: $dossier->getId()->toRfc4122(),
            submittedAt: $dossier->getSubmittedAt()->format(\DATE_ATOM),
            clientName: $client->getFullName(),
            email: $client->getEmail(),
            phone: (string) ($request['phone'] ?? $client->getPhone()),
            projectType: CreditProjectTypeEnum::from((string) ($request['projectType'] ?? CreditProjectTypeEnum::PERSONAL_GOODS->value)),
            requestedAmountCents: (int) ($request['requestedAmountCents'] ?? 0),
            durationMonths: (int) ($request['durationMonths'] ?? 0),
            decision: $dossier->getDecision(),
            callbackStatus: $dossier->getCallbackStatus(),
            callbackUpdatedAt: $dossier->getCallbackUpdatedAt()?->format(\DATE_ATOM),
            advisorName: $dossier->getCallbackAdvisor()?->getFullName(),
            priority: $assessment->priority,
            priorityReason: $assessment->reason,
            workflowStatus: $dossier->getWorkflowStatus(),
            debtRatioAfterBps: (int) ($dossier->getScoringSummary()['debtRatioAfterBps'] ?? 0),
        );
    }

    private function detail(Dossier $dossier): CrmDossierDetailDto
    {
        $now = new \DateTimeImmutable();
        $client = $dossier->getClient();
        $assessment = $this->priorityResolver->assess($dossier, $now);

        return new CrmDossierDetailDto(
            dossier: $this->presenter->detail($dossier, $now),
            clientName: $client->getFullName(),
            email: $client->getEmail(),
            phone: (string) ($dossier->getRequestSnapshot()['phone'] ?? $client->getPhone()),
            callbackStatus: $dossier->getCallbackStatus(),
            advisorNote: $dossier->getAdvisorNote(),
            callbackUpdatedAt: $dossier->getCallbackUpdatedAt()?->format(\DATE_ATOM),
            advisorName: $dossier->getCallbackAdvisor()?->getFullName(),
            acquisition: $dossier->getAcquisition(),
            priority: $assessment->priority,
            priorityReason: $assessment->reason,
            workflowTransitions: $dossier->getWorkflowStatus()->advisorTransitions(),
            emails: array_map(static fn (EmailNotification $email): EmailNotificationDto => new EmailNotificationDto(
                type: $email->getType(),
                label: $email->getType()->label(),
                subject: $email->getSubject(),
                recipient: $email->getRecipient(),
                status: $email->getStatus(),
                sentAt: $email->getSentAt()->format(\DATE_ATOM),
            ), $this->notificationRepository->findByDossier($dossier)),
        );
    }
}
