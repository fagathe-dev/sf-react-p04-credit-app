<?php

declare(strict_types=1);

namespace Credit\Core\Enum;

/**
 * Moment où une règle est évaluée.
 */
enum RuleStageEnum: string
{
    /** Recevabilité de la demande (territoire, âge, consentements) : un échec renvoie une erreur 422 avant tout calcul. */
    case ADMISSION = 'ADMISSION';
    /** Éligibilité à un produit, pour un montant et une durée donnés. */
    case PRODUCT = 'PRODUCT';
}
