<?php

declare(strict_types=1);

namespace App\Crm\Priority\Rule;

use App\Crm\Priority\CallbackPriorityRuleInterface;
use App\Crm\Priority\DossierMetrics;
use App\Enum\CallbackPriorityEnum;
use App\Enum\DossierWorkflowStatusEnum;
use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\Catalog\InMemoryProductCatalog;
use Credit\Core\Util\FrenchFormatter;

/**
 * RG-01 — P1 : dossier chaud ajustable.
 *
 * Résultat de moins de 72 h, endettement projeté dans la zone grise de rattrapage (plus de 33 % et
 * jusqu'à 40 % ; la tranche 33–35 % des dossiers avec offres est rattachée à P1), avec blindage :
 * reste à vivre ≥ 850 €, revenus du foyer ≥ 1 500 €, durée demandée inférieure à la durée maximale du produit.
 */
final readonly class HotAdjustableRule implements CallbackPriorityRuleInterface
{
    public const int MAX_AGE_SECONDS = 72 * self::HOUR;
    public const int MIN_DEBT_RATIO_EXCLUSIVE_BPS = 3_300;
    public const int MAX_DEBT_RATIO_BPS = 4_000;
    public const int MIN_REMAINDER_CENTS = 85_000;
    public const int MIN_INCOME_CENTS = 150_000;

    public function __construct(
        private InMemoryProductCatalog $catalog,
    ) {}

    public function getPriority(): CallbackPriorityEnum
    {
        return CallbackPriorityEnum::P1;
    }

    public function matches(DossierMetrics $metrics, \DateTimeImmutable $now): bool
    {
        $age = $metrics->ageSeconds($now);

        return \in_array($metrics->workflowStatus, [DossierWorkflowStatusEnum::QUOTES_GENERATED, DossierWorkflowStatusEnum::NO_OFFER_ADVISOR_QUEUE], true)
            && $age >= 0 && $age < self::MAX_AGE_SECONDS
            && $metrics->debtRatioAfterBps > self::MIN_DEBT_RATIO_EXCLUSIVE_BPS
            && $metrics->debtRatioAfterBps <= self::MAX_DEBT_RATIO_BPS
            && $metrics->remainderToLiveCents >= self::MIN_REMAINDER_CENTS
            && $metrics->weightedIncomeCents >= self::MIN_INCOME_CENTS
            && $metrics->durationMonths < $this->maxDurationMonths($metrics);
    }

    public function describe(DossierMetrics $metrics, \DateTimeImmutable $now): string
    {
        return \sprintf(
            'Endettement projeté de %s : proposer un allongement (jusqu\'à %d mois au lieu de %d) ou un montant plus bas pour repasser sous le seuil automatique.',
            FrenchFormatter::bps($metrics->debtRatioAfterBps),
            $this->maxDurationMonths($metrics),
            $metrics->durationMonths,
        );
    }

    /**
     * Durée maximale du produit de référence du résultat ; à défaut, la plus longue des produits du projet.
     */
    private function maxDurationMonths(DossierMetrics $metrics): int
    {
        $reference = null === $metrics->referenceOfferCode ? null : $this->catalog->findOffer($metrics->referenceOfferCode);
        if (null !== $reference) {
            return $reference->criteria->maxDurationMonths;
        }

        $durations = array_map(static fn (OfferConfigDto $offer): int => $offer->criteria->maxDurationMonths, $this->catalog->getOffersByProject($metrics->projectType));

        return [] === $durations ? 0 : max($durations);
    }
}
