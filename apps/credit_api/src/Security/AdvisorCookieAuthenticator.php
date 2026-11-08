<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\AdvisorRepository;
use App\Security\Session\SessionAudienceEnum;
use App\Security\Session\SessionTokenManager;
use App\Security\User\SessionUserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Firewall « crm » : cookie advisor_session, rôle ROLE_ADVISOR. Un conseiller désactivé perd sa session.
 */
final class AdvisorCookieAuthenticator extends AbstractCookieAuthenticator
{
    public function __construct(
        SessionTokenManager $tokenManager,
        private readonly AdvisorRepository $advisorRepository,
    ) {
        parent::__construct($tokenManager);
    }

    protected function getAudience(): SessionAudienceEnum
    {
        return SessionAudienceEnum::ADVISOR;
    }

    protected function findUser(Uuid $id): ?SessionUserInterface
    {
        return $this->advisorRepository->find($id);
    }
}
