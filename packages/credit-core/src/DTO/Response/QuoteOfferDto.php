<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Response;

use Credit\Core\Enum\CreditProjectTypeEnum;

/**
 * Offre chiffrée d'un partenaire, figée dans le dossier au moment du résultat.
 */
final readonly class QuoteOfferDto
{
    public function __construct(
        public string $partnerCode,
        public string $partnerName,
        public string $partnerLogo,
        public string $offerCode,
        public string $productName,
        public CreditProjectTypeEnum $projectType,
        public int $amountCents,
        public int $durationMonths,
        public int $nominalRateBps,
        public int $aprBps,
        public int $monthlyInstallmentCents,
        public int $monthlyInsuranceCents,
        public int $totalInterestCents,
        public int $totalFinancingCostCents,
        public int $debtRatioAfterBps,
        public bool $isBestRate = false,
    ) {}

    public function withBestRate(bool $isBestRate): self
    {
        return new self(
            $this->partnerCode,
            $this->partnerName,
            $this->partnerLogo,
            $this->offerCode,
            $this->productName,
            $this->projectType,
            $this->amountCents,
            $this->durationMonths,
            $this->nominalRateBps,
            $this->aprBps,
            $this->monthlyInstallmentCents,
            $this->monthlyInsuranceCents,
            $this->totalInterestCents,
            $this->totalFinancingCostCents,
            $this->debtRatioAfterBps,
            $isBestRate,
        );
    }
}
