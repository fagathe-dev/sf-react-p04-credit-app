<?php

declare(strict_types=1);

namespace App\Security\Session;

/**
 * Public d'une session : chaque public a son cookie, son firewall et son rôle.
 * Un cookie client ne donne aucun accès aux routes /api/v1/crm/**, et inversement.
 */
enum SessionAudienceEnum: string
{
    case CLIENT = 'client';
    case ADVISOR = 'advisor';

    public function cookieName(): string
    {
        return match ($this) {
            self::CLIENT => 'client_session',
            self::ADVISOR => 'advisor_session',
        };
    }
}
