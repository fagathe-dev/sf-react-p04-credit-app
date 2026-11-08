<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Priorité d'appel d'un dossier dans la file des conseillers (RG-01 à RG-04).
 */
enum CallbackPriorityEnum: string
{
    /** RG-01 : dossier chaud ajustable (< 72 h, endettement 33 à 40 %). */
    case P1 = 'P1';
    /** RG-02 : gros montant éligible non souscrit (2 h à 36 h). */
    case P1_BIS = 'P1_BIS';
    /** RG-03 : devis standard éligible non souscrit (24 h à 48 h). */
    case P2 = 'P2';
    /** RG-04 : dernière relance avant expiration des offres (> 5 à 7 jours). */
    case P3 = 'P3';
    /** Aucune règle : pas d'appel à prévoir. */
    case NONE = 'NONE';

    /** Ordre de la file : 1 = appeler en premier. */
    public function rank(): int
    {
        return match ($this) {
            self::P1 => 1,
            self::P1_BIS => 2,
            self::P2 => 3,
            self::P3 => 4,
            self::NONE => 9,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::P1 => 'P1 · Dossier chaud ajustable',
            self::P1_BIS => 'P1 bis · Gros montant hésitant',
            self::P2 => 'P2 · Relance devis standard',
            self::P3 => 'P3 · Dernière relance avant expiration',
            self::NONE => 'Sans priorité',
        };
    }
}
