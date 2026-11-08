<?php

declare(strict_types=1);

namespace App\Crm\Priority;

use App\Enum\CallbackPriorityEnum;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Règle de priorisation des appels (RG-01 à RG-04). Ajouter une règle = ajouter une classe :
 * CallbackPriorityResolver les évalue par ordre de priorité, la première qui correspond l'emporte.
 */
#[AutoconfigureTag(self::TAG)]
interface CallbackPriorityRuleInterface
{
    public const string TAG = 'app.callback_priority_rule';

    public const int HOUR = 3_600;
    public const int DAY = 86_400;

    public function getPriority(): CallbackPriorityEnum;

    public function matches(DossierMetrics $metrics, \DateTimeImmutable $now): bool;

    /** Motif affiché au conseiller (ce qu'il doit proposer). */
    public function describe(DossierMetrics $metrics, \DateTimeImmutable $now): string;
}
