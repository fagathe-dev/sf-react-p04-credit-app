<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\ClientRepository;
use App\Security\Session\SessionAudienceEnum;
use App\Security\Session\SessionTokenManager;
use App\Security\User\SessionUserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Firewall « api » : cookie client_session, rôle ROLE_CLIENT.
 */
final class ClientCookieAuthenticator extends AbstractCookieAuthenticator
{
    public function __construct(
        SessionTokenManager $tokenManager,
        private readonly ClientRepository $clientRepository,
    ) {
        parent::__construct($tokenManager);
    }

    protected function getAudience(): SessionAudienceEnum
    {
        return SessionAudienceEnum::CLIENT;
    }

    protected function findUser(Uuid $id): ?SessionUserInterface
    {
        return $this->clientRepository->find($id);
    }
}
