<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Score;

use Credit\Core\Scoring\Model\ApplicantProfile;

final class SeniorityCriterion implements ScoreCriterionInterface
{
    public function getCode(): string
    {
        return 'SENIORITY';
    }

    public function getLabel(): string
    {
        return 'Ancienneté professionnelle';
    }

    public function points(ApplicantProfile $profile, int $debtRatioAfterBps): int
    {
        return match (true) {
            $profile->seniorityMonths >= 36 => 100,
            $profile->seniorityMonths >= 12 => 50,
            $profile->seniorityMonths < 6 => -50,
            default => 0,
        };
    }
}
