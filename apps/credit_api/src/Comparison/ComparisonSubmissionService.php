<?php

declare(strict_types=1);

namespace App\Comparison;

use App\ClientSpace\ClientRegistrationService;
use App\DTO\Response\ComparisonSubmissionDto;
use App\Entity\Client;
use App\Http\ApiResponseTrait;
use App\Http\PayloadReader;
use App\Security\Session\SessionAudienceEnum;
use App\Security\Session\SessionCookieFactory;
use App\Security\Session\SessionTokenManager;
use Credit\Core\DTO\Request\ComparisonRequestDto;
use Credit\Core\Scoring\CreditComparator;
use Credit\Core\Scoring\ScoringEngine;
use Symfony\Component\HttpFoundation\Request;

/**
 * POST /api/v1/compare : valide la demande (422 avant tout calcul), calcule la décision,
 * enregistre le dossier et répond ComparisonSubmissionDto (HTTP 200).
 */
final class ComparisonSubmissionService
{
    use ApiResponseTrait;

    public function __construct(
        private readonly PayloadReader $payloadReader,
        private readonly ScoringEngine $scoringEngine,
        private readonly CreditComparator $comparator,
        private readonly ClientRegistrationService $registration,
        private readonly SessionTokenManager $tokenManager,
        private readonly SessionCookieFactory $cookieFactory,
    ) {}

    public function submit(Request $request, ?Client $sessionClient): object
    {
        $payload = $this->payloadReader->read($request, ComparisonRequestDto::class);
        if (!$payload->isValid()) {
            return $this->sendUnprocessable($payload->violations);
        }

        /** @var ComparisonRequestDto $dto */
        $dto = $payload->dto;
        $today = new \DateTimeImmutable('today');
        $existing = $this->registration->findClient($dto->normalizedEmail());

        // Recevabilité : territoire, âge, consentements (création d'espace exigée pour un nouveau client)
        $failures = $this->scoringEngine->checkAdmission($dto, null === $existing, $today);
        if ([] !== $failures) {
            $violations = [];
            foreach ($failures as $failure) {
                $violations[$failure->field ?? $failure->ruleCode] = $failure->message;
            }

            return $this->sendUnprocessable($violations, 'Votre demande ne peut pas être étudiée.');
        }

        $result = $this->comparator->compare($dto, $today);
        $outcome = $this->registration->register($dto, $result, $existing, $sessionClient);

        $headers = [];
        if ($outcome->opensSession()) {
            $token = $this->tokenManager->issue($outcome->client, SessionAudienceEnum::CLIENT);
            $headers['Set-Cookie'] = (string) $this->cookieFactory->create(SessionAudienceEnum::CLIENT, $token);
        }

        return $this->sendJson(
            new ComparisonSubmissionDto($outcome->clientStatus, $outcome->dossier->getId()->toRfc4122()),
            headers: $headers,
        );
    }
}
