<?php

declare(strict_types=1);

namespace Credit\Core\Scoring;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\DTO\Request\ComparisonRequestDto;
use Credit\Core\DTO\Response\AdjustmentSuggestionDto;
use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\AdjustmentKindEnum;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\AdjustmentSimulation;
use Credit\Core\Scoring\Model\ApplicantProfile;
use Credit\Core\Scoring\Model\OfferEvaluation;
use Credit\Core\Util\FrenchFormatter;

/**
 * Cherche un montant plus bas ou une durée plus longue qui rend le dossier financable.
 *
 * Pour chaque produit : montant du montant demandé jusqu'au minimum du produit par pas de 500 €,
 * durée de la durée demandée jusqu'au maximum du produit. Jamais de montant supérieur à la demande.
 */
final readonly class FinancingAdjustmentSimulator
{
    public const int AMOUNT_STEP_CENTS = 50_000;
    public const int MAX_SUGGESTIONS = 3;
    private const int BPS = 10_000;

    public function __construct(
        private ScoringEngine $engine,
        private QuoteFactory $quoteFactory,
    ) {}

    /**
     * @param list<OfferEvaluation> $requestEvaluations évaluations au montant et à la durée demandés (aucune éligible)
     */
    public function simulate(ComparisonRequestDto $request, ApplicantProfile $profile, array $requestEvaluations, \DateTimeImmutable $today): AdjustmentSimulation
    {
        $requestedAmount = $request->requestedAmountCents;
        $requestedDuration = $request->durationMonths;

        $requestFailures = [];
        $structuralFailures = [];
        /** @var array<AdjustmentKindEnum::*, array{evaluation: OfferEvaluation, distance: int, request: OfferEvaluation}|null> $best */
        $best = [];

        foreach ($requestEvaluations as $atRequest) {
            $offer = $atRequest->offer;
            $bestVariant = $this->evaluate($request, $profile, $offer, $this->bestAmount($offer, $requestedAmount), $this->bestDuration($offer, $requestedDuration), $today);

            $blockingCodes = array_map(static fn (RuleExecutionDto $e): string => $e->ruleCode, $bestVariant->failures());

            // Sévérité effective : un critère ADJUSTABLE qui échoue encore dans la meilleure variante devient STRUCTURAL
            foreach ($atRequest->failures() as $failure) {
                $requestFailures[] = \in_array($failure->ruleCode, $blockingCodes, true)
                    ? $failure->withSeverity(RuleSeverityEnum::STRUCTURAL)
                    : $failure;
            }

            if (!$bestVariant->passed()) {
                $structuralFailures[$offer->offerCode] = array_map(
                    static fn (RuleExecutionDto $e): RuleExecutionDto => $e->withSeverity(RuleSeverityEnum::STRUCTURAL),
                    $bestVariant->failures(),
                );
                continue;
            }

            foreach ($this->candidates($request, $profile, $offer, $today) as $kind => $candidate) {
                $current = $best[$kind] ?? null;
                if (null === $current || self::isCloser($candidate, $current)) {
                    $best[$kind] = $candidate + ['request' => $atRequest];
                }
            }
        }

        // Le compromis n'est proposé que si ni la durée seule ni le montant seul ne suffisent
        if (isset($best[AdjustmentKindEnum::LONGER_DURATION->value]) || isset($best[AdjustmentKindEnum::LOWER_AMOUNT->value])) {
            unset($best[AdjustmentKindEnum::COMPROMISE->value]);
        }

        uasort($best, static fn (array $a, array $b): int => [$a['distance'], $a['evaluation']->offer->aprBps] <=> [$b['distance'], $b['evaluation']->offer->aprBps]);

        $suggestions = [];
        foreach ($best as $kind => $candidate) {
            $suggestions[] = $this->buildSuggestion(AdjustmentKindEnum::from($kind), $candidate['evaluation'], $candidate['distance'], $requestedAmount, $requestedDuration);
        }
        $suggestions = \array_slice($suggestions, 0, self::MAX_SUGGESTIONS);

        $reasons = [];
        $closest = array_values($best)[0]['request'] ?? null;
        if ($closest instanceof OfferEvaluation) {
            $reasons = array_values(array_unique(array_map(static fn (RuleExecutionDto $e): string => $e->message, $closest->failures())));
        }

        return new AdjustmentSimulation($suggestions, $requestFailures, $structuralFailures, $reasons);
    }

    /**
     * Meilleure piste de chaque nature pour un produit.
     *
     * @return array<string, array{evaluation: OfferEvaluation, distance: int}>
     */
    private function candidates(ComparisonRequestDto $request, ApplicantProfile $profile, OfferConfigDto $offer, \DateTimeImmutable $today): array
    {
        $amount = $request->requestedAmountCents;
        $duration = $request->durationMonths;
        $criteria = $offer->criteria;
        $candidates = [];

        // 1. Durée plus longue, montant inchangé : l'allongement le plus court qui passe
        if ($criteria->acceptsAmount($amount)) {
            foreach ($this->longerDurations($offer, $duration) as $d) {
                $evaluation = $this->evaluate($request, $profile, $offer, $amount, $d, $today);
                if ($evaluation->passed()) {
                    $candidates[AdjustmentKindEnum::LONGER_DURATION->value] = ['evaluation' => $evaluation, 'distance' => self::distance($amount, $duration, $amount, $d)];
                    break;
                }
            }
        }

        // 2. Montant plus bas, durée inchangée : le montant maximal qui passe
        if ($criteria->acceptsDuration($duration)) {
            foreach ($this->lowerAmounts($offer, $amount) as $a) {
                $evaluation = $this->evaluate($request, $profile, $offer, $a, $duration, $today);
                if ($evaluation->passed()) {
                    $candidates[AdjustmentKindEnum::LOWER_AMOUNT->value] = ['evaluation' => $evaluation, 'distance' => self::distance($amount, $duration, $a, $duration)];
                    break;
                }
            }
        }

        // 3. Compromis : montant plus bas et durée plus longue, la combinaison la plus proche de la demande
        if ([] === $candidates) {
            foreach ($this->longerDurations($offer, $duration) as $d) {
                foreach ($this->lowerAmounts($offer, $amount) as $a) {
                    $evaluation = $this->evaluate($request, $profile, $offer, $a, $d, $today);
                    if (!$evaluation->passed()) {
                        continue;
                    }

                    $distance = self::distance($amount, $duration, $a, $d);
                    $current = $candidates[AdjustmentKindEnum::COMPROMISE->value] ?? null;
                    if (null === $current || $distance < $current['distance']) {
                        $candidates[AdjustmentKindEnum::COMPROMISE->value] = ['evaluation' => $evaluation, 'distance' => $distance];
                    }
                    break; // montants décroissants : le premier qui passe est le plus proche pour cette durée
                }
            }
        }

        return $candidates;
    }

    /**
     * Durées strictement plus longues que la demande (et au moins égales au minimum du produit), croissantes.
     *
     * @return iterable<int>
     */
    private function longerDurations(OfferConfigDto $offer, int $requestedDuration): iterable
    {
        for ($d = max($requestedDuration + 1, $offer->criteria->minDurationMonths); $d <= $offer->criteria->maxDurationMonths; ++$d) {
            yield $d;
        }
    }

    /**
     * Montants strictement inférieurs à la demande, par pas de 500 €, dans les bornes du produit, puis le minimum du produit.
     *
     * @return iterable<int>
     */
    private function lowerAmounts(OfferConfigDto $offer, int $requestedAmount): iterable
    {
        $min = $offer->criteria->minAmountCents;
        $max = $offer->criteria->maxAmountCents;
        $last = null;

        for ($a = $requestedAmount - self::AMOUNT_STEP_CENTS; $a >= $min; $a -= self::AMOUNT_STEP_CENTS) {
            if ($a > $max) {
                continue;
            }
            yield $last = $a;
        }

        if ($min < $requestedAmount && $last !== $min && $min <= $max) {
            yield $min;
        }
    }

    /** Montant de la meilleure variante : le minimum du produit (jamais plus que la demande). */
    private function bestAmount(OfferConfigDto $offer, int $requestedAmount): int
    {
        return $requestedAmount >= $offer->criteria->minAmountCents ? $offer->criteria->minAmountCents : $requestedAmount;
    }

    /** Durée de la meilleure variante : le maximum du produit (jamais moins que la demande). */
    private function bestDuration(OfferConfigDto $offer, int $requestedDuration): int
    {
        return $requestedDuration <= $offer->criteria->maxDurationMonths ? $offer->criteria->maxDurationMonths : $requestedDuration;
    }

    private function evaluate(ComparisonRequestDto $request, ApplicantProfile $profile, OfferConfigDto $offer, int $amount, int $duration, \DateTimeImmutable $today): OfferEvaluation
    {
        return $this->engine->evaluateOffer($request, $profile, $offer, $amount, $duration, $today);
    }

    /**
     * Éloignement : baisse relative du montant + hausse relative de la durée, en points de base.
     */
    private static function distance(int $requestedAmount, int $requestedDuration, int $amount, int $duration): int
    {
        return intdiv(max(0, $requestedAmount - $amount) * self::BPS, $requestedAmount)
            + intdiv(max(0, $duration - $requestedDuration) * self::BPS, $requestedDuration);
    }

    /**
     * @param array{evaluation: OfferEvaluation, distance: int} $candidate
     * @param array{evaluation: OfferEvaluation, distance: int} $current
     */
    private static function isCloser(array $candidate, array $current): bool
    {
        return [$candidate['distance'], $candidate['evaluation']->offer->aprBps, $candidate['evaluation']->schedule->monthlyInstallmentCents]
            < [$current['distance'], $current['evaluation']->offer->aprBps, $current['evaluation']->schedule->monthlyInstallmentCents];
    }

    private function buildSuggestion(AdjustmentKindEnum $kind, OfferEvaluation $evaluation, int $distance, int $requestedAmount, int $requestedDuration): AdjustmentSuggestionDto
    {
        $amount = $evaluation->amountCents();
        $duration = $evaluation->durationMonths();

        $label = match ($kind) {
            AdjustmentKindEnum::LONGER_DURATION => \sprintf('Allonger la durée à %d mois (au lieu de %d mois)', $duration, $requestedDuration),
            AdjustmentKindEnum::LOWER_AMOUNT => \sprintf('Emprunter %s (au lieu de %s)', FrenchFormatter::cents($amount, false), FrenchFormatter::cents($requestedAmount, false)),
            AdjustmentKindEnum::COMPROMISE => \sprintf('Emprunter %s sur %d mois', FrenchFormatter::cents($amount, false), $duration),
        };

        return $this->quoteFactory->suggestion($evaluation, $kind, $label, $distance);
    }
}
