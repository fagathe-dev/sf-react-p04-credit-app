<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

enum RuleSeverityEnum: string
{
    /** Non ajustable : ni le montant ni la durée ne lèvent le blocage. */
    case STRUCTURAL = 'STRUCTURAL';
    /** Levable en baissant le montant ou en allongeant la durée. */
    case ADJUSTABLE = 'ADJUSTABLE';
}
