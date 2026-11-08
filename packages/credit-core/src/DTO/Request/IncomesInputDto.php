<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Revenus mensuels du foyer, en centimes (étape 5 du tunnel).
 */
final readonly class IncomesInputDto
{
    private const int MAX_CENTS = 10_000_000; // 100 000 € par mois

    public function __construct(
        #[Assert\PositiveOrZero(message: 'Le salaire doit être positif.')]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $netMonthlySalaryCents = 0,
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $coBorrowerNetSalaryCents = 0,
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $familyAllowancesCents = 0,
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $pensionsReceivedCents = 0,
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $rentalIncomeGrossCents = 0,
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(self::MAX_CENTS)]
        public int $otherMonthlyIncomeCents = 0,
    ) {}
}
