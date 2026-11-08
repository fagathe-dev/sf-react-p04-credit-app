<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Model;

use Credit\Core\DTO\Response\ScoreContributionDto;
use Credit\Core\Enum\RiskGradeEnum;

final readonly class ScoreResult
{
    /**
     * @param list<ScoreContributionDto> $contributions
     */
    public function __construct(
        public int $score,
        public RiskGradeEnum $grade,
        public array $contributions,
    ) {}
}
