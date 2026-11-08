<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Charges mensuelles existantes du foyer, en centimes (étape 6 du tunnel).
 */
final readonly class ChargesInputDto
{
    private const int MAX_CENTS = 10_000_000;

    public function __construct(
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $rentMonthlyCents = 0,
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $mortgageMonthlyCents = 0,
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $consumerLoansMonthlyCents = 0,
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $alimonyPaidMonthlyCents = 0,
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $otherChargesMonthlyCents = 0,
    ) {}
}
