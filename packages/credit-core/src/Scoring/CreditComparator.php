<?php

declare(strict_types=1);

namespace Credit\Core\Scoring;

use Credit\Core\Catalog\InMemoryProductCatalog;
use Credit\Core\DTO\Request\ComparisonRequestDto;
use Credit\Core\DTO\Response\AdvisorFallbackDto;
use Credit\Core\DTO\Response\ComparisonResultDto;
use Credit\Core\DTO\Response\RejectionDto;
use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\DTO\Response\ScoringSummaryDto;
use Credit\Core\Enum\ScoringDecisionEnum;
use Credit\Core\Scoring\Calculator\DebtRatioCalculator;
use Credit\Core\Scoring\Model\AdjustmentSimulation;
use Credit\Core\Scoring\Model\ApplicantProfile;
use Credit\Core\Scoring\Model\OfferEvaluation;

/**
 * Orchestre la comparaison : profil, offres immédiates, puis rattrapage ou refus (section 4.4).
 *
 * La recevabilité (ScoringEngine::checkAdmission) est contrôlée en amont par l'appelant.
 */
final readonly class CreditComparator
{
    public function __construct(
        private InMemoryProductCatalog $catalog,
        private ScoringEngine $engine,
        private InstantQuoteMatcher $matcher,
        private FinancingAdjustmentSimulator $simulator,
        private DebtRatioCalculator $debtRatio,
    ) {}

    public function compare(ComparisonRequestDto $request, ?\DateTimeImmutable $today = null): ComparisonResultDto
    {
        $today ??= new \DateTimeImmutable('today');
        $offers = $this->catalog->getOffersByProject($request->projectType);
        $profile = $this->engine->buildProfile($request, $today);
        $evaluations = $this->matcher->evaluate($request, $profile, $offers, $today);

        // 1. Au moins une offre éligible avec le montant et la durée demandés
        $quotes = $this->matcher->quotes($evaluations);
        if ([] !== $quotes) {
            return new ComparisonResultDto(
                decision: ScoringDecisionEnum::INSTANT_OFFERS,
                scoringSummary: $this->summary($profile, $this->matcher->bestEligible($evaluations)),
                quotes: $quotes,
            );
        }

        $summary = $this->summary($profile, $this->referenceEvaluation($evaluations));

        // 2. Variantes : montant plus bas ou durée plus longue
        $simulation = $this->simulator->simulate($request, $profile, $evaluations, $today);

        if ($simulation->isFinanceable()) {
            return new ComparisonResultDto(
                decision: ScoringDecisionEnum::ADVISOR_CALLBACK_NEEDED,
                scoringSummary: $summary,
                advisorFallback: $this->advisorFallback($request, $simulation),
                ruleExecutions: $simulation->requestFailures,
            );
        }

        // 3. Aucun montant ni aucune durée ne rend le dossier financable
        return new ComparisonResultDto(
            decision: ScoringDecisionEnum::HARD_REJECT,
            scoringSummary: $summary,
            rejection: $this->rejection($simulation, \count($offers)),
            ruleExecutions: $simulation->requestFailures,
        );
    }

    private function summary(ApplicantProfile $profile, ?OfferEvaluation $reference): ScoringSummaryDto
    {
        $installment = $reference?->schedule->monthlyInstallmentCents ?? 0;
        $debtRatioAfter = $reference->debtRatioAfterBps ?? $profile->debtRatioBeforeBps;
        $score = $reference->score ?? $this->engine->score($profile, $debtRatioAfter);

        return new ScoringSummaryDto(
            score: $score->score,
            riskGrade: $score->grade,
            weightedIncomeCents: $profile->weightedIncomeCents,
            existingChargesCents: $profile->existingChargesCents,
            debtRatioBeforeBps: $profile->debtRatioBeforeBps,
            debtRatioAfterBps: $debtRatioAfter,
            referenceMonthlyInstallmentCents: $installment,
            remainderToLiveCents: $this->debtRatio->remainderToLive($profile->weightedIncomeCents, $profile->existingChargesCents, $installment),
            minRemainderToLiveCents: $profile->householdMinRemainderCents,
            referenceOfferCode: $reference?->offer->offerCode,
            scoreBreakdown: $score->contributions,
        );
    }

    /**
     * Sans offre éligible : produit au plus petit TAEG du projet, au montant et à la durée demandés.
     *
     * @param list<OfferEvaluation> $evaluations
     */
    private function referenceEvaluation(array $evaluations): ?OfferEvaluation
    {
        usort($evaluations, static fn (OfferEvaluation $a, OfferEvaluation $b): int => [$a->offer->aprBps, $a->offer->offerCode] <=> [$b->offer->aprBps, $b->offer->offerCode]);

        return $evaluations[0] ?? null;
    }

    private function advisorFallback(ComparisonRequestDto $request, AdjustmentSimulation $simulation): AdvisorFallbackDto
    {
        $phone = self::formatPhone($request->phone);

        $message = $request->consentAdvisorCallback
            ? \sprintf('Un conseiller spécialisé va vous rappeler au %s pour étudier avec vous un montant plus bas ou une durée plus longue.', $phone)
            : 'Votre demande ne passe pas telle quelle, mais des solutions existent. Demandez à être rappelé par un conseiller depuis votre espace client pour en discuter.';

        return new AdvisorFallbackDto(
            reasons: $simulation->reasons,
            suggestions: $simulation->suggestions,
            callbackRequested: $request->consentAdvisorCallback,
            phone: $phone,
            message: $message,
        );
    }

    private function rejection(AdjustmentSimulation $simulation, int $offerCount): RejectionDto
    {
        if (0 === $offerCount) {
            return new RejectionDto(['Aucune offre de nos partenaires ne correspond à ce type de projet.']);
        }

        // Nombre de produits bloqués par chaque règle structurelle
        $counts = [];
        foreach ($simulation->structuralFailures as $failures) {
            foreach (array_unique(array_map(static fn (RuleExecutionDto $e): string => $e->ruleCode, $failures)) as $code) {
                $counts[$code] = ($counts[$code] ?? 0) + 1;
            }
        }
        arsort($counts);

        $reasons = [];
        foreach ($counts as $code => $count) {
            $rule = $this->engine->findRule($code);
            if (null !== $rule) {
                $reasons[] = $rule->getRejectionReason($count === $offerCount);
            }
        }

        return new RejectionDto(array_values(array_unique($reasons)) ?: ['Votre situation ne correspond aux critères d\'aucun de nos partenaires pour ce projet.']);
    }

    /** 0612345678 → « 06 12 34 56 78 ». */
    private static function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? $phone;

        return 10 === \strlen($digits) ? trim(chunk_split($digits, 2, ' ')) : $phone;
    }
}
