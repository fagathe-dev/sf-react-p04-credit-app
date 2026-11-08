<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Model;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\DTO\Response\RuleExecutionDto;

/**
 * Évaluation d'un produit pour un montant et une durée.
 */
final readonly class OfferEvaluation
{
    /**
     * @param list<RuleExecutionDto> $executions
     */
    public function __construct(
        public OfferConfigDto $offer,
        public LoanSchedule $schedule,
        public int $debtRatioAfterBps,
        public int $remainderToLiveCents,
        public ScoreResult $score,
        public array $executions,
    ) {}

    public function passed(): bool
    {
        foreach ($this->executions as $execution) {
            if (!$execution->passed) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<RuleExecutionDto>
     */
    public function failures(): array
    {
        return array_values(array_filter($this->executions, static fn (RuleExecutionDto $e): bool => !$e->passed));
    }

    public function amountCents(): int
    {
        return $this->schedule->amountCents;
    }

    public function durationMonths(): int
    {
        return $this->schedule->durationMonths;
    }
}
