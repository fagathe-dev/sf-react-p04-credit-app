<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

/**
 * Suivi d'appel d'un dossier par un conseiller (credit_crm).
 */
enum CallbackStatusEnum: string
{
    case TO_CALL = 'TO_CALL';
    case NO_ANSWER = 'NO_ANSWER';
    case CALLBACK_SCHEDULED = 'CALLBACK_SCHEDULED';
    case ADJUSTED = 'ADJUSTED';
    case DECLINED = 'DECLINED';
    case CLOSED = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::TO_CALL => 'À appeler',
            self::NO_ANSWER => 'Pas de réponse',
            self::CALLBACK_SCHEDULED => 'Rappel planifié',
            self::ADJUSTED => 'Dossier ajusté',
            self::DECLINED => 'Refus du client',
            self::CLOSED => 'Clos',
        };
    }
}
