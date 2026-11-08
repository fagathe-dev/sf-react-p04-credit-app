<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\DTO;

use Credit\Core\Enum\EmploymentStatusEnum;
use Credit\Core\Enum\RiskGradeEnum;

/**
 * Critères d'éligibilité d'un produit, convertis en centimes et points de base.
 */
final readonly class OfferCriteriaDto
{
    /**
     * @param list<EmploymentStatusEnum> $allowedEmploymentStatuses
     */
    public function __construct(
        public int $minAmountCents,
        public int $maxAmountCents,
        public int $minDurationMonths,
        public int $maxDurationMonths,
        public int $minMonthlyIncomeCents,
        public int $maxDebtRatioBps,
        public int $minRemainderToLiveCents,
        public int $minSeniorityMonths,
        public RiskGradeEnum $minRiskGrade,
        public array $allowedEmploymentStatuses,
    ) {}

    public function acceptsEmploymentStatus(EmploymentStatusEnum $status): bool
    {
        return \in_array($status, $this->allowedEmploymentStatuses, true);
    }

    public function acceptsAmount(int $amountCents): bool
    {
        return $amountCents >= $this->minAmountCents && $amountCents <= $this->maxAmountCents;
    }

    public function acceptsDuration(int $durationMonths): bool
    {
        return $durationMonths >= $this->minDurationMonths && $durationMonths <= $this->maxDurationMonths;
    }
}
