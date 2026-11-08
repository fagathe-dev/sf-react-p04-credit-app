<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

enum MaritalStatusEnum: string
{
    case SINGLE = 'SINGLE';
    case MARRIED = 'MARRIED';
    case PACS = 'PACS';
    case DIVORCED = 'DIVORCED';
    case WIDOWED = 'WIDOWED';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Célibataire',
            self::MARRIED => 'Marié(e)',
            self::PACS => 'Pacsé(e)',
            self::DIVORCED => 'Divorcé(e)',
            self::WIDOWED => 'Veuf / veuve',
        };
    }
}
