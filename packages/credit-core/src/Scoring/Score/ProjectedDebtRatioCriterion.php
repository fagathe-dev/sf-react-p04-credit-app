<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Score;

use Credit\Core\Scoring\Model\ApplicantProfile;

final class ProjectedDebtRatioCriterion implements ScoreCriterionInterface
{
    public function getCode(): string
    {
        return 'PROJECTED_DEBT_RATIO';
    }

    public function getLabel(): string
    {
        return 'Endettement projeté';
    }

    public function points(ApplicantProfile $profile, int $debtRatioAfterBps): int
    {
        return match (true) {
            $debtRatioAfterBps <= 2_500 => 100,
            $debtRatioAfterBps <= 3_000 => 50,
            $debtRatioAfterBps <= 3_300 => 0,
            $debtRatioAfterBps <= 3_500 => -100,
            default => -300,
        };
    }
}
