<?php

declare(strict_types=1);

namespace Credit\Core\Util;

/**
 * Formatage français des montants et des taux dans les messages métier.
 */
final class FrenchFormatter
{
    private const string NBSP = "\u{00A0}";
    private const string NARROW_NBSP = "\u{202F}";

    /** 1500000 → « 15 000,00 € » ; avec $withCents = false → « 15 000 € ». */
    public static function cents(int $cents, bool $withCents = true): string
    {
        $negative = $cents < 0;
        $abs = abs($cents);
        $euros = intdiv($abs, 100);
        $rest = $abs % 100;

        $formatted = number_format($euros, 0, ',', self::NARROW_NBSP);

        if ($withCents) {
            $formatted .= ',' . str_pad((string) $rest, 2, '0', STR_PAD_LEFT);
        }

        return ($negative ? '-' : '') . $formatted . self::NBSP . '€';
    }

    /** 3720 → « 37,2 % » (une décimale par défaut), 380 → « 3,80 % » avec $decimals = 2. */
    public static function bps(int $bps, int $decimals = 1): string
    {
        $negative = $bps < 0;
        $abs = abs($bps);
        $divisor = 10 ** (2 - $decimals);
        $value = 0 === $decimals ? Decimal::divRound($abs, 100) : Decimal::divRound($abs, $divisor);

        $integer = 0 === $decimals ? $value : intdiv($value, 10 ** $decimals);
        $fraction = 0 === $decimals ? '' : ',' . str_pad((string) ($value % (10 ** $decimals)), $decimals, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '') . $integer . $fraction . self::NBSP . '%';
    }
}
