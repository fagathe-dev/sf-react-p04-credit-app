<?php

declare(strict_types=1);

namespace App\ClientSpace;

enum OtpVerificationStatusEnum
{
    case VALID;
    /** Code faux, expiré ou absent. */
    case INVALID;
    /** Nombre maximal d'essais atteint : le code est invalidé. */
    case TOO_MANY_ATTEMPTS;
}
