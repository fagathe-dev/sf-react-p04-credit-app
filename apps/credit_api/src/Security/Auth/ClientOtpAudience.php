<?php

declare(strict_types=1);

namespace App\Security\Auth;

use App\Entity\Advisor;
use App\Entity\Client;
use App\Entity\Dossier;
use App\Entity\OtpCode;
use App\Notification\DossierNotifier;
use App\Repository\ClientRepository;
use App\Repository\DossierRepository;
use App\Security\Session\SessionAudienceEnum;
use Credit\Core\Enum\DossierStatusEnum;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

/**
 * Clients de l'espace client (credit_client). Un code lié à un dossier ne rattache que ce dossier.
 */
final readonly class ClientOtpAudience implements OtpAudienceInterface
{
    public function __construct(
        private ClientRepository $clientRepository,
        private DossierRepository $dossierRepository,
        private DossierNotifier $notifier,
        #[Autowire(env: 'FRONTEND_URL')]
        private string $frontendUrl,
    ) {}

    public function getAudience(): SessionAudienceEnum
    {
        return SessionAudienceEnum::CLIENT;
    }

    public function findEligibleUser(string $email): ?Client
    {
        return $this->clientRepository->findOneByEmail($email);
    }

    public function resolveDossier(Client|Advisor $user, ?string $dossierId): ?Dossier
    {
        if (!$user instanceof Client || null === $dossierId || !Uuid::isValid($dossierId)) {
            return null;
        }

        return $this->dossierRepository->findPendingForClient(Uuid::fromString($dossierId), $user);
    }

    public function getLoginUrl(Client|Advisor $user, ?Dossier $dossier): string
    {
        $query = ['email' => $user->getEmail()];
        if (null !== $dossier) {
            $query['dossierId'] = $dossier->getId()->toRfc4122();
        }

        return rtrim($this->frontendUrl, '/') . '/code?' . http_build_query($query);
    }

    public function onVerified(Client|Advisor $user, OtpCode $otp): array
    {
        \assert($user instanceof Client);

        $dossier = $otp->getDossier();
        $activatedDossier = null;
        if (null !== $dossier && DossierStatusEnum::PENDING_VERIFICATION === $dossier->getStatus()) {
            $dossier->setStatus(DossierStatusEnum::ACTIVE);
            $this->dossierRepository->save($dossier, flush: false);
            $activatedDossier = $dossier;
        }

        $this->clientRepository->save($user->setLastLoginAt(new \DateTimeImmutable()));

        // Le dossier devient visible : e-mails de confirmation et de transmission
        if (null !== $activatedDossier) {
            $this->notifier->notifyDossierRegistered($activatedDossier);
        }

        return ['dossierId' => $dossier?->getId()->toRfc4122()];
    }
}
