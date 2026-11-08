<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

/**
 * Grade de risque, du meilleur (A) au moins bon (E).
 */
enum RiskGradeEnum: string
{
    case GRADE_A = 'GRADE_A';
    case GRADE_B = 'GRADE_B';
    case GRADE_C = 'GRADE_C';
    case GRADE_D = 'GRADE_D';
    case GRADE_E = 'GRADE_E';

    /** Seuils de score (inclus) dans l'ordre décroissant. */
    private const array MIN_SCORES = [
        'GRADE_A' => 800,
        'GRADE_B' => 650,
        'GRADE_C' => 500,
        'GRADE_D' => 350,
        'GRADE_E' => 0,
    ];

    public static function fromScore(int $score): self
    {
        foreach (self::MIN_SCORES as $grade => $minScore) {
            if ($score >= $minScore) {
                return self::from($grade);
            }
        }

        return self::GRADE_E;
    }

    /** Rang : 1 pour A, 5 pour E. */
    public function rank(): int
    {
        return array_search($this->value, array_keys(self::MIN_SCORES), true) + 1;
    }

    /** Vrai si ce grade est au moins aussi bon que $minimum. */
    public function isAtLeast(self $minimum): bool
    {
        return $this->rank() <= $minimum->rank();
    }

    public function letter(): string
    {
        return substr($this->value, -1);
    }
}
