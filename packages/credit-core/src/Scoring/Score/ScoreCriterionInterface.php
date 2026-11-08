<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Score;

use Credit\Core\Scoring\Model\ApplicantProfile;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Critère du score sur 1000 (base 500). Ajouter un critère = ajouter une classe.
 */
#[AutoconfigureTag(self::TAG)]
interface ScoreCriterionInterface
{
    public const string TAG = 'credit_core.score_criterion';

    public function getCode(): string;

    public function getLabel(): string;

    public function points(ApplicantProfile $profile, int $debtRatioAfterBps): int;
}
