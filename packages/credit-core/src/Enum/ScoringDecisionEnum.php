<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

enum ScoringDecisionEnum: string
{
    /** Au moins un produit éligible avec le montant et la durée demandés. */
    case INSTANT_OFFERS = 'INSTANT_OFFERS';
    /** Financable avec un montant plus bas ou une durée plus longue. */
    case ADVISOR_CALLBACK_NEEDED = 'ADVISOR_CALLBACK_NEEDED';
    /** Aucun montant ni aucune durée ne rend le dossier financable. */
    case HARD_REJECT = 'HARD_REJECT';

    public function label(): string
    {
        return match ($this) {
            self::INSTANT_OFFERS => 'Offres immédiates',
            self::ADVISOR_CALLBACK_NEEDED => 'Dossier à ajuster',
            self::HARD_REJECT => 'Dossier non financable',
        };
    }
}
