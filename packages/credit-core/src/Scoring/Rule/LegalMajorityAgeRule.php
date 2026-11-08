<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Scoring\Model\RuleContext;
use Credit\Core\Util\DateCalculator;

/**
 * L'emprunteur, et l'éventuel co-emprunteur, a entre 18 ans révolus et 80 ans.
 */
final class LegalMajorityAgeRule extends AbstractAdmissionRule
{
    public const int MIN_AGE = 18;
    public const int MAX_AGE = 80;

    public function getCode(): string
    {
        return 'LEGAL_MAJORITY_AGE';
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $request = $context->request;

        if (!$this->isWithinRange($request->birthDate, $context->today)) {
            return $this->fail('L\'emprunteur doit avoir entre 18 ans révolus et 80 ans.', 'birthDate');
        }

        if ($request->hasCoBorrower && null !== $request->coBorrower && !$this->isWithinRange($request->coBorrower->birthDate, $context->today)) {
            return $this->fail('Le co-emprunteur doit avoir entre 18 ans révolus et 80 ans.', 'coBorrower.birthDate');
        }

        return $this->pass();
    }

    private function isWithinRange(string $birthDate, \DateTimeImmutable $today): bool
    {
        $age = DateCalculator::ageInYears($birthDate, $today);

        return null !== $age && $age >= self::MIN_AGE && $age <= self::MAX_AGE;
    }
}
