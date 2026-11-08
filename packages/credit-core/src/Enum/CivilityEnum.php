<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

enum CivilityEnum: string
{
    case MR = 'MR';
    case MRS = 'MRS';
    /** Civilité non binaire ou non précisée : aucune formule de politesse n'est affichée. */
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::MR => 'M.',
            self::MRS => 'Mme',
            self::OTHER => 'Autre',
        };
    }
}
