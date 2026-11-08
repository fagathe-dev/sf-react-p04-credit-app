<?php

declare(strict_types=1);

namespace App\ClientSpace;

use App\Entity\Client;
use App\Notification\DossierNotifier;
use App\Repository\ClientRepository;
use App\Repository\DossierRepository;
use App\Security\Auth\ClientOtpAudience;
use Credit\Core\DTO\Request\ComparisonRequestDto;
use Credit\Core\DTO\Response\ComparisonResultDto;
use Credit\Core\Enum\ClientStatusEnum;
use Credit\Core\Enum\DossierStatusEnum;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Trait\LoggerTrait;

/**
 * Reconnaît ou crée le client, puis enregistre le dossier avec son résultat figé (section 7.2).
 *
 * 1. E-mail inconnu : client créé, dossier ACTIVE, session ouverte (CREATED).
 * 2. E-mail connu sans session valide pour ce client : dossier PENDING_VERIFICATION + code OTP lié (OTP_REQUIRED).
 * 3. E-mail connu avec une session valide pour ce même client : dossier ACTIVE (AUTHENTICATED).
 *
 * Un dossier visible (ACTIVE) déclenche les e-mails de confirmation et de transmission aux partenaires.
 */
final class ClientRegistrationService
{
    use LoggerTrait;

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly DossierRepository $dossierRepository,
        private readonly OtpService $otpService,
        private readonly ClientOtpAudience $clientAudience,
        private readonly DossierFactory $dossierFactory,
        private readonly DossierNotifier $notifier,
    ) {}

    public function findClient(string $email): ?Client
    {
        return $this->clientRepository->findOneByEmail($email);
    }

    public function register(ComparisonRequestDto $request, ComparisonResultDto $result, ?Client $existing, ?Client $sessionClient): RegistrationOutcome
    {
        if (null === $existing) {
            $client = $this->dossierFactory->createClient($request);
            $status = ClientStatusEnum::CREATED;
        } elseif (null !== $sessionClient && $sessionClient->getId()->equals($existing->getId())) {
            $client = $existing;
            $status = ClientStatusEnum::AUTHENTICATED;
        } else {
            $client = $existing;
            $status = ClientStatusEnum::OTP_REQUIRED;
        }

        $dossier = $this->dossierFactory->createDossier(
            $client,
            $request,
            $result,
            ClientStatusEnum::OTP_REQUIRED === $status ? DossierStatusEnum::PENDING_VERIFICATION : DossierStatusEnum::ACTIVE,
        );

        if (ClientStatusEnum::CREATED === $status) {
            $client->setLastLoginAt(new \DateTimeImmutable());
            $this->clientRepository->save($client, flush: false);
        }

        $this->dossierRepository->save($dossier);

        if (ClientStatusEnum::OTP_REQUIRED === $status) {
            // Code lié à ce dossier : sa vérification l'active (et déclenche alors les e-mails du dossier).
            $this->otpService->issue($client, $this->clientAudience->getLoginUrl($client, $dossier), $dossier, enforceResendDelay: false);
        } else {
            $this->notifier->notifyDossierRegistered($dossier);
        }

        $this->generateLog(LoggerLevelEnum::Info, [
            'message' => 'Dossier enregistré.',
            'data' => [
                'dossier' => $dossier->getId()->toRfc4122(),
                'client' => $client->getId()->toRfc4122(),
                'clientStatus' => $status->value,
                'decision' => $result->decision->value,
            ],
        ], ['action' => 'dossier.registered']);

        return new RegistrationOutcome($status, $client, $dossier);
    }
}
