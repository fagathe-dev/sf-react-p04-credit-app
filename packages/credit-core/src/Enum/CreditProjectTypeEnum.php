<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

/**
 * Type de projet financé. Les valeurs (minuscules) sont celles des fichiers JSON du catalogue.
 */
enum CreditProjectTypeEnum: string
{
    case GREEN_LOAN = 'green_loan';
    case AUTO = 'auto';
    case HOME_IMPROVEMENT = 'home_improvement';
    case PERSONAL_GOODS = 'personal_goods';
    case CASH_TREASURY = 'cash_treasury';

    public function label(): string
    {
        return match ($this) {
            self::GREEN_LOAN => 'Crédit vert',
            self::AUTO => 'Prêt auto',
            self::HOME_IMPROVEMENT => 'Prêt travaux',
            self::PERSONAL_GOODS => 'Prêt personnel',
            self::CASH_TREASURY => 'Trésorerie express',
        };
    }
}
