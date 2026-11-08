<?php

declare(strict_types=1);

namespace App\ClientSpace;

use App\DTO\Request\ConsentUpdateDto;
use App\DTO\Request\SubscribeOfferDto;
use App\DTO\Response\ClientProfileDto;
use App\DTO\Response\ConsentStateDto;
use App\Entity\Client;
use App\Entity\Dossier;
use App\Http\ApiResponseTrait;
use App\Http\PayloadReader;
use App\Repository\DossierRepository;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

/**
 * Espace client : profil, consentements, dossiers (/api/v1/me).
 */
final class ClientSpaceService
{
    use ApiResponseTrait;
    use LoggerTrait;

    public function __construct(
        private readonly DossierRepository $dossierRepository,
        private readonly DossierPresenter $presenter,
        private readonly PayloadReader $payloadReader,
    ) {}

    public function getProfile(Client $client): object
    {
        return $this->sendJson($this->buildProfile($client));
    }

    /**
     * Donne ou retire le consentement de rappel sur tous les dossiers actifs du client.
     */
    public function updateConsents(Client $client, Request $request): object
    {
        $payload = $this->payloadReader->read($request, ConsentUpdateDto::class);
        if (!$payload->isValid()) {
            return $this->sendUnprocessable($payload->violations);
        }

        /** @var ConsentUpdateDto $dto */
        $dto = $payload->dto;
        $consent = (bool) $dto->consentAdvisorCallback;

        foreach ($this->dossierRepository->findActiveByClient($client) as $dossier) {
            $dossier->setConsentAdvisorCallback($consent);
            $this->dossierRepository->save($dossier, flush: false);
        }
        $this->dossierRepository->flush();

        $this->generateLog(LoggerLevelEnum::Info, [
            'message' => $consent ? 'Consentement de rappel donné.' : 'Consentement de rappel retiré.',
            'data' => ['client' => $client->getId()->toRfc4122()],
        ], ['action' => 'client.consent_advisor_callback']);

        return $this->sendJson($this->buildProfile($client));
    }

    public function listDossiers(Client $client): object
    {
        return $this->sendJson(array_map(
            $this->presenter->summary(...),
            $this->dossierRepository->findActiveByClient($client),
        ));
    }

    public function getDossier(Client $client, string $id): object
    {
        $dossier = Uuid::isValid($id) ? $this->dossierRepository->findActiveForClient(Uuid::fromString($id), $client) : null;

        if (null === $dossier) {
            return $this->sendNotFound('Dossier introuvable.');
        }

        return $this->sendJson($this->presenter->detail($dossier));
    }

    /**
     * Le client choisit une offre : le dossier passe en QUOTE_SUBSCRIBED (validation par un conseiller ensuite).
     */
    public function subscribe(Client $client, string $id, Request $request): object
    {
        $dossier = Uuid::isValid($id) ? $this->dossierRepository->findActiveForClient(Uuid::fromString($id), $client) : null;
        if (null === $dossier) {
            return $this->sendNotFound('Dossier introuvable.');
        }

        $payload = $this->payloadReader->read($request, SubscribeOfferDto::class);
        if (!$payload->isValid()) {
            return $this->sendUnprocessable($payload->violations);
        }

        /** @var SubscribeOfferDto $dto */
        $dto = $payload->dto;
        $now = new \DateTimeImmutable();

        if (!$dossier->canSubscribe($dto->offerCode, $now)) {
            $message = match (true) {
                !$dossier->hasQuote($dto->offerCode) => 'Cette offre ne fait pas partie de votre dossier.',
                $dossier->areQuotesExpired($now) => 'Vos offres ont expiré : refaites une demande pour obtenir des offres à jour.',
                default => 'Une offre a déjà été choisie pour ce dossier.',
            };

            return $this->sendUnprocessable(['offerCode' => $message], 'Cette offre ne peut pas être choisie.');
        }

        $this->dossierRepository->save($dossier->subscribe($dto->offerCode, $now));

        $this->generateLog(LoggerLevelEnum::Info, [
            'message' => 'Offre choisie par le client.',
            'data' => ['dossier' => $dossier->getId()->toRfc4122(), 'offerCode' => $dto->offerCode],
        ], ['action' => 'client.offer_subscribed']);

        return $this->sendJson($this->presenter->detail($dossier, $now));
    }

    private function buildProfile(Client $client): ClientProfileDto
    {
        $dossiers = $this->dossierRepository->findActiveByClient($client);
        $latest = $dossiers[0] ?? null;

        $callbackDates = array_filter(array_map(static fn (Dossier $d): ?\DateTimeImmutable => $d->getConsentAdvisorCallbackAt(), $dossiers));
        $callbackAt = [] === $callbackDates ? null : max($callbackDates);

        return new ClientProfileDto(
            id: $client->getId()->toRfc4122(),
            email: $client->getEmail(),
            civility: $client->getCivility(),
            firstName: $client->getFirstName(),
            lastName: $client->getLastName(),
            birthDate: $client->getBirthDate()->format('Y-m-d'),
            phone: $client->getPhone(),
            createdAt: $client->getCreatedAt()->format(\DATE_ATOM),
            lastLoginAt: $client->getLastLoginAt()?->format(\DATE_ATOM),
            consents: new ConsentStateDto(
                clientSpaceCreatedAt: $client->getConsentClientSpaceCreatedAt()->format(\DATE_ATOM),
                partnerTransmissionAt: $latest?->getConsentPartnerTransmissionAt()->format(\DATE_ATOM),
                advisorCallbackAt: $callbackAt?->format(\DATE_ATOM),
                advisorCallback: null !== $callbackAt,
            ),
        );
    }
}
