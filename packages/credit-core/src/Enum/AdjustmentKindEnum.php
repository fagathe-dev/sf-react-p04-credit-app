<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

/**
 * Nature d'une piste d'ajustement proposée pour un dossier ADVISOR_CALLBACK_NEEDED.
 */
enum AdjustmentKindEnum: string
{
    /** Montant inchangé, durée allongée. */
    case LONGER_DURATION = 'LONGER_DURATION';
    /** Durée inchangée, montant réduit. */
    case LOWER_AMOUNT = 'LOWER_AMOUNT';
    /** Montant réduit et durée allongée. */
    case COMPROMISE = 'COMPROMISE';

    public function label(): string
    {
        return match ($this) {
            self::LONGER_DURATION => 'Durée plus longue',
            self::LOWER_AMOUNT => 'Montant plus bas',
            self::COMPROMISE => 'Compromis montant et durée',
        };
    }
}
