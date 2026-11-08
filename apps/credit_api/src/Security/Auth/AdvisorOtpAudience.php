<?php

declare(strict_types=1);

namespace App\Security\Auth;

use App\Entity\Advisor;
use App\Entity\Client;
use App\Entity\Dossier;
use App\Entity\OtpCode;
use App\Repository\AdvisorRepository;
use App\Security\Session\SessionAudienceEnum;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Conseillers du back-office (credit_crm) : seuls les conseillers actifs reçoivent un code.
 */
final readonly class AdvisorOtpAudience implements OtpAudienceInterface
{
    public function __construct(
        private AdvisorRepository $advisorRepository,
        #[Autowire(env: 'CRM_URL')]
        private string $crmUrl,
    ) {}

    public function getAudience(): SessionAudienceEnum
    {
        return SessionAudienceEnum::ADVISOR;
    }

    public function findEligibleUser(string $email): ?Advisor
    {
        return $this->advisorRepository->findActiveByEmail($email);
    }

    public function resolveDossier(Client|Advisor $user, ?string $dossierId): ?Dossier
    {
        return null;
    }

    public function getLoginUrl(Client|Advisor $user, ?Dossier $dossier): string
    {
        return rtrim($this->crmUrl, '/') . '/code?' . http_build_query(['email' => $user->getEmail()]);
    }

    public function onVerified(Client|Advisor $user, OtpCode $otp): array
    {
        \assert($user instanceof Advisor);

        $this->advisorRepository->save($user->setLastLoginAt(new \DateTimeImmutable()));

        return [];
    }
}
