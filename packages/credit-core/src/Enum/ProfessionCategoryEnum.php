<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

enum ProfessionCategoryEnum: string
{
    case EXECUTIVE = 'EXECUTIVE';
    case EMPLOYEE = 'EMPLOYEE';
    case WORKER = 'WORKER';
    case INTERMEDIATE = 'INTERMEDIATE';
    case SELF_EMPLOYED = 'SELF_EMPLOYED';
    case INACTIVE = 'INACTIVE';

    public function label(): string
    {
        return match ($this) {
            self::EXECUTIVE => 'Cadre',
            self::EMPLOYEE => 'Employé',
            self::WORKER => 'Ouvrier',
            self::INTERMEDIATE => 'Profession intermédiaire',
            self::SELF_EMPLOYED => 'Artisan, commerçant, profession libérale',
            self::INACTIVE => 'Sans activité professionnelle',
        };
    }
}
