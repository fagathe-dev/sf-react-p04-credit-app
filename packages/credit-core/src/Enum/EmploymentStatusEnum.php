<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

enum EmploymentStatusEnum: string
{
    case CDI = 'CDI';
    case CIVIL_SERVANT = 'CIVIL_SERVANT';
    case CDD = 'CDD';
    case INDEPENDENT = 'INDEPENDENT';
    case RETIRED = 'RETIRED';
    case STUDENT = 'STUDENT';
    case UNEMPLOYED = 'UNEMPLOYED';

    public function label(): string
    {
        return match ($this) {
            self::CDI => 'CDI',
            self::CIVIL_SERVANT => 'Fonctionnaire',
            self::CDD => 'CDD / intérim',
            self::INDEPENDENT => 'Indépendant',
            self::RETIRED => 'Retraité',
            self::STUDENT => 'Étudiant',
            self::UNEMPLOYED => 'Sans emploi',
        };
    }
}
