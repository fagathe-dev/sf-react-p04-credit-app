<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Score;

use Credit\Core\Enum\HousingStatusEnum;
use Credit\Core\Scoring\Model\ApplicantProfile;

final class HousingCriterion implements ScoreCriterionInterface
{
    private const array POINTS = [
        HousingStatusEnum::OWNER_WITHOUT_MORTGAGE->value => 120,
        HousingStatusEnum::OWNER_WITH_MORTGAGE->value => 80,
        HousingStatusEnum::TENANT->value => 20,
        HousingStatusEnum::HOSTED_FREE->value => 0,
        HousingStatusEnum::COMPANY_HOUSING->value => 0,
    ];

    public function getCode(): string
    {
        return 'HOUSING';
    }

    public function getLabel(): string
    {
        return 'Logement';
    }

    public function points(ApplicantProfile $profile, int $debtRatioAfterBps): int
    {
        return self::POINTS[$profile->housingStatus->value];
    }
}
