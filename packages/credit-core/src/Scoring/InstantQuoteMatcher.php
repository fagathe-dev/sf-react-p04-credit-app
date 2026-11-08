<?php

declare(strict_types=1);

namespace Credit\Core\Scoring;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\DTO\Request\ComparisonRequestDto;
use Credit\Core\DTO\Response\QuoteOfferDto;
use Credit\Core\Scoring\Model\ApplicantProfile;
use Credit\Core\Scoring\Model\OfferEvaluation;

/**
 * Croise le profil scoré avec les produits du projet, au montant et à la durée demandés.
 */
final readonly class InstantQuoteMatcher
{
    public function __construct(
        private ScoringEngine $engine,
        private QuoteFactory $quoteFactory,
    ) {}

    /**
     * @param list<OfferConfigDto> $offers produits du projet demandé
     *
     * @return list<OfferEvaluation>
     */
    public function evaluate(ComparisonRequestDto $request, ApplicantProfile $profile, array $offers, \DateTimeImmutable $today): array
    {
        return array_map(
            fn (OfferConfigDto $offer): OfferEvaluation => $this->engine->evaluateOffer(
                $request,
                $profile,
                $offer,
                $request->requestedAmountCents,
                $request->durationMonths,
                $today,
            ),
            $offers,
        );
    }

    /**
     * Offres éligibles triées par TAEG croissant (puis mensualité) ; la première porte isBestRate.
     *
     * @param list<OfferEvaluation> $evaluations
     *
     * @return list<QuoteOfferDto>
     */
    public function quotes(array $evaluations): array
    {
        $eligible = array_values(array_filter($evaluations, static fn (OfferEvaluation $e): bool => $e->passed()));

        usort($eligible, self::compare(...));

        $quotes = [];
        foreach ($eligible as $index => $evaluation) {
            $quotes[] = $this->quoteFactory->quote($evaluation, 0 === $index);
        }

        return $quotes;
    }

    /**
     * Meilleure évaluation éligible (même ordre que quotes()).
     *
     * @param list<OfferEvaluation> $evaluations
     */
    public function bestEligible(array $evaluations): ?OfferEvaluation
    {
        $eligible = array_values(array_filter($evaluations, static fn (OfferEvaluation $e): bool => $e->passed()));
        usort($eligible, self::compare(...));

        return $eligible[0] ?? null;
    }

    private static function compare(OfferEvaluation $a, OfferEvaluation $b): int
    {
        return [$a->offer->aprBps, $a->schedule->monthlyInstallmentCents, $a->offer->offerCode]
            <=> [$b->offer->aprBps, $b->schedule->monthlyInstallmentCents, $b->offer->offerCode];
    }
}
