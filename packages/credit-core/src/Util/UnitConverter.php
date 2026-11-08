<?php

declare(strict_types=1);

namespace Credit\Core\Util;

/**
 * Conversion des unités humaines du catalogue JSON (euros, pourcentages)
 * vers les unités du code métier (centimes, points de base).
 */
final class UnitConverter
{
    /** 15000 (€) → 1500000 (centimes). */
    public static function eurosToCents(int|float|string $euros): int
    {
        return Decimal::scaleToInt($euros, 100);
    }

    /** 3.8 (%) → 380 (points de base). */
    public static function percentToBps(int|float|string $percent): int
    {
        return Decimal::scaleToInt($percent, 100);
    }
}
