<?php

declare(strict_types=1);

namespace Credit\Core\Util;

/**
 * Arithmétique décimale exacte (bcmath) : aucun float ne circule dans le calcul financier.
 */
final class Decimal
{
    public const int SCALE = 20;

    /**
     * Arrondi au plus proche entier, demi vers l'extérieur de zéro.
     */
    public static function roundToInt(string $value): int
    {
        $half = str_starts_with($value, '-') ? '-0.5' : '0.5';

        return (int) bcadd($value, $half, 0);
    }

    /**
     * Arrondi à l'entier supérieur (valeurs positives).
     */
    public static function ceilToInt(string $value): int
    {
        $truncated = bcadd($value, '0', 0);

        return bccomp($value, $truncated, self::SCALE) > 0 ? (int) $truncated + 1 : (int) $truncated;
    }

    /**
     * Division entière arrondie au plus proche : round($numerator / $denominator).
     */
    public static function divRound(int $numerator, int $denominator): int
    {
        if (0 === $denominator) {
            throw new \DivisionByZeroError('Division par zéro.');
        }

        return self::roundToInt(bcdiv((string) $numerator, (string) $denominator, self::SCALE));
    }

    /**
     * Convertit une valeur numérique lue dans un JSON (euros, pourcentage) en entier multiplié par $factor.
     * Le float éventuel est converti en chaîne (représentation la plus courte) avant tout calcul.
     */
    public static function scaleToInt(int|float|string $value, int $factor): int
    {
        $string = \is_float($value) ? self::floatToString($value) : (string) $value;

        if (!is_numeric($string)) {
            throw new \InvalidArgumentException(\sprintf('Valeur numérique invalide : "%s".', $string));
        }

        return self::roundToInt(bcmul($string, (string) $factor, self::SCALE));
    }

    private static function floatToString(float $value): string
    {
        // var_export donne la représentation la plus courte qui redonne le même float (ex. 4.23)
        $string = var_export($value, true);

        if (str_contains($string, 'E') || str_contains($string, 'e')) {
            $string = number_format($value, 10, '.', '');
        }

        return $string;
    }
}
