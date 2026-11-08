<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Score;

use Credit\Core\Enum\EmploymentStatusEnum;
use Credit\Core\Scoring\Model\ApplicantProfile;

final class EmploymentStatusCriterion implements ScoreCriterionInterface
{
    private const array POINTS = [
        EmploymentStatusEnum::CDI->value => 200,
        EmploymentStatusEnum::CIVIL_SERVANT->value => 200,
        EmploymentStatusEnum::RETIRED->value => 150,
        EmploymentStatusEnum::INDEPENDENT->value => 80,
        EmploymentStatusEnum::CDD->value => 20,
        EmploymentStatusEnum::STUDENT->value => -50,
        EmploymentStatusEnum::UNEMPLOYED->value => -400,
    ];

    public function getCode(): string
    {
        return 'EMPLOYMENT_STATUS';
    }

    public function getLabel(): string
    {
        return 'Contrat professionnel';
    }

    public function points(ApplicantProfile $profile, int $debtRatioAfterBps): int
    {
        return self::POINTS[$profile->employmentStatus->value];
    }
}
