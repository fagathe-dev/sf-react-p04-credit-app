<?php

declare(strict_types=1);

namespace Credit\Core\Scoring;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\DTO\Request\ComparisonRequestDto;
use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\DTO\Response\ScoreContributionDto;
use Credit\Core\Enum\RiskGradeEnum;
use Credit\Core\Enum\RuleStageEnum;
use Credit\Core\Scoring\Calculator\DebtRatioCalculator;
use Credit\Core\Scoring\Calculator\LoanMathCalculator;
use Credit\Core\Scoring\Model\ApplicantProfile;
use Credit\Core\Scoring\Model\OfferEvaluation;
use Credit\Core\Scoring\Model\RuleContext;
use Credit\Core\Scoring\Model\ScoreResult;
use Credit\Core\Scoring\Rule\ScoringRuleInterface;
use Credit\Core\Scoring\Score\ScoreCriterionInterface;
use Credit\Core\Util\DateCalculator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Exécute les règles, calcule le score sur 1000 et le grade de risque.
 */
final class ScoringEngine
{
    public const int BASE_SCORE = 500;
    public const int MIN_SCORE = 0;
    public const int MAX_SCORE = 1000;

    /** @var list<ScoringRuleInterface> */
    private array $admissionRules = [];
    /** @var list<ScoringRuleInterface> */
    private array $productRules = [];

    /** @var list<ScoreCriterionInterface> */
    private array $criteria;

    /**
     * @param iterable<ScoringRuleInterface>    $rules
     * @param iterable<ScoreCriterionInterface> $criteria
     */
    public function __construct(
        #[AutowireIterator(ScoringRuleInterface::TAG)]
        iterable $rules,
        #[AutowireIterator(ScoreCriterionInterface::TAG)]
        iterable $criteria,
        private readonly LoanMathCalculator $loanMath,
        private readonly DebtRatioCalculator $debtRatio,
    ) {
        foreach ($rules as $rule) {
            if (RuleStageEnum::ADMISSION === $rule->getStage()) {
                $this->admissionRules[] = $rule;
            } else {
                $this->productRules[] = $rule;
            }
        }

        $this->criteria = [...$criteria];
    }

    /**
     * Règles de recevabilité (territoire, âge, consentements).
     *
     * @return list<RuleExecutionDto> règles échouées (vide si la demande est recevable)
     */
    public function checkAdmission(ComparisonRequestDto $request, bool $isNewClient, \DateTimeImmutable $today): array
    {
        $context = RuleContext::forAdmission($request, $isNewClient, $today);
        $failures = [];

        foreach ($this->admissionRules as $rule) {
            $execution = $rule->evaluate($context);
            if (!$execution->passed) {
                $failures[] = $execution;
            }
        }

        return $failures;
    }

    public function buildProfile(ComparisonRequestDto $request, \DateTimeImmutable $today): ApplicantProfile
    {
        $income = $this->debtRatio->weightedIncome($request->incomes, $request->hasCoBorrower);
        $charges = $this->debtRatio->totalCharges($request->charges);

        return new ApplicantProfile(
            projectType: $request->projectType,
            requestedAmountCents: $request->requestedAmountCents,
            requestedDurationMonths: $request->durationMonths,
            weightedIncomeCents: $income,
            existingChargesCents: $charges,
            debtRatioBeforeBps: $this->debtRatio->debtRatioBps($charges, $income),
            householdMinRemainderCents: $this->debtRatio->householdMinRemainder($request->hasCoBorrower, $request->dependentChildrenCount),
            employmentStatus: $request->employmentStatus,
            seniorityMonths: DateCalculator::monthsSince($request->contractStartDate, $today),
            housingStatus: $request->housingStatus,
            hasCoBorrower: $request->hasCoBorrower,
            dependentChildrenCount: $request->dependentChildrenCount,
        );
    }

    public function score(ApplicantProfile $profile, int $debtRatioAfterBps): ScoreResult
    {
        $score = self::BASE_SCORE;
        $contributions = [];

        foreach ($this->criteria as $criterion) {
            $points = $criterion->points($profile, $debtRatioAfterBps);
            $score += $points;
            $contributions[] = new ScoreContributionDto($criterion->getCode(), $criterion->getLabel(), $points);
        }

        $score = max(self::MIN_SCORE, min(self::MAX_SCORE, $score));

        return new ScoreResult($score, RiskGradeEnum::fromScore($score), $contributions);
    }

    /**
     * Évalue un produit pour un montant et une durée : échéancier, endettement projeté, reste à vivre, score et règles.
     */
    public function evaluateOffer(
        ComparisonRequestDto $request,
        ApplicantProfile $profile,
        OfferConfigDto $offer,
        int $amountCents,
        int $durationMonths,
        \DateTimeImmutable $today,
    ): OfferEvaluation {
        $schedule = $this->loanMath->schedule($amountCents, $durationMonths, $offer->nominalRateBps, $offer->insuranceRateBps);
        $chargesAfter = $profile->existingChargesCents + $schedule->monthlyInstallmentCents;
        $debtRatioAfter = $this->debtRatio->debtRatioBps($chargesAfter, $profile->weightedIncomeCents);
        $remainder = $this->debtRatio->remainderToLive($profile->weightedIncomeCents, $profile->existingChargesCents, $schedule->monthlyInstallmentCents);
        $score = $this->score($profile, $debtRatioAfter);

        $context = RuleContext::forOffer($request, $today, $profile, $offer, $schedule, $debtRatioAfter, $remainder, $score);

        $executions = [];
        foreach ($this->productRules as $rule) {
            $executions[] = $rule->evaluate($context);
        }

        return new OfferEvaluation($offer, $schedule, $debtRatioAfter, $remainder, $score, $executions);
    }

    public function findRule(string $code): ?ScoringRuleInterface
    {
        foreach ([...$this->admissionRules, ...$this->productRules] as $rule) {
            if ($rule->getCode() === $code) {
                return $rule;
            }
        }

        return null;
    }
}
