<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

/**
 * Statut renvoyé en fin de tunnel par POST /api/v1/compare.
 */
enum ClientStatusEnum: string
{
    /** Nouveau client : compte créé, session ouverte. */
    case CREATED = 'CREATED';
    /** Client connu sans session valide : un code OTP a été envoyé. */
    case OTP_REQUIRED = 'OTP_REQUIRED';
    /** Client connu avec une session valide : dossier rattaché directement. */
    case AUTHENTICATED = 'AUTHENTICATED';
}
