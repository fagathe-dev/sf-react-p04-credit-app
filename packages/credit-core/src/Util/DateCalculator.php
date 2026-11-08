<?php

declare(strict_types=1);

namespace Credit\Core\Util;

final class DateCalculator
{
    public static function parse(?string $date): ?\DateTimeImmutable
    {
        if (null === $date || '' === $date) {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return false === $parsed ? null : $parsed;
    }

    /** Âge en années révolues à la date $today. */
    public static function ageInYears(string $birthDate, \DateTimeImmutable $today): ?int
    {
        $birth = self::parse($birthDate);

        if (null === $birth || $birth > $today) {
            return null;
        }

        return $birth->diff($today)->y;
    }

    /** Nombre de mois entiers écoulés depuis $since (0 si absent ou futur). */
    public static function monthsSince(?string $since, \DateTimeImmutable $today): int
    {
        $start = self::parse($since);

        if (null === $start || $start > $today) {
            return 0;
        }

        $diff = $start->diff($today);

        return $diff->y * 12 + $diff->m;
    }
}
