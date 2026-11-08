<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

enum DossierStatusEnum: string
{
    /** En attente de la vérification OTP du client : invisible pour tous. */
    case PENDING_VERIFICATION = 'PENDING_VERIFICATION';
    case ACTIVE = 'ACTIVE';
}
