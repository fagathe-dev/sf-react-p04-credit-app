<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

enum HousingStatusEnum: string
{
    case TENANT = 'TENANT';
    case OWNER_WITH_MORTGAGE = 'OWNER_WITH_MORTGAGE';
    case OWNER_WITHOUT_MORTGAGE = 'OWNER_WITHOUT_MORTGAGE';
    case HOSTED_FREE = 'HOSTED_FREE';
    case COMPANY_HOUSING = 'COMPANY_HOUSING';

    public function label(): string
    {
        return match ($this) {
            self::TENANT => 'Locataire',
            self::OWNER_WITH_MORTGAGE => 'Propriétaire avec crédit immobilier',
            self::OWNER_WITHOUT_MORTGAGE => 'Propriétaire sans crédit immobilier',
            self::HOSTED_FREE => 'Hébergé gratuitement',
            self::COMPANY_HOUSING => 'Logement de fonction',
        };
    }
}
