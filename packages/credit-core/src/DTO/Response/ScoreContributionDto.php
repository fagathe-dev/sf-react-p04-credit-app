<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Response;

/**
 * Points apportés par un critère du score (lecture conseiller).
 */
final readonly class ScoreContributionDto
{
    public function __construct(
        public string $criterion,
        public string $label,
        public int $points,
    ) {}
}
