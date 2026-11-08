<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Model;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\DTO\Request\ComparisonRequestDto;

/**
 * Données disponibles pour une règle.
 *
 * - Étape ADMISSION : request, isNewClient et today.
 * - Étape PRODUCT : en plus, le profil, le produit, l'échéancier et les indicateurs projetés.
 */
final readonly class RuleContext
{
    private function __construct(
        public ComparisonRequestDto $request,
        public \DateTimeImmutable $today,
        public bool $isNewClient,
        public ?ApplicantProfile $profile = null,
        public ?OfferConfigDto $offer = null,
        public ?LoanSchedule $schedule = null,
        public ?int $debtRatioAfterBps = null,
        public ?int $remainderToLiveCents = null,
        public ?ScoreResult $score = null,
    ) {}

    public static function forAdmission(ComparisonRequestDto $request, bool $isNewClient, \DateTimeImmutable $today): self
    {
        return new self($request, $today, $isNewClient);
    }

    public static function forOffer(
        ComparisonRequestDto $request,
        \DateTimeImmutable $today,
        ApplicantProfile $profile,
        OfferConfigDto $offer,
        LoanSchedule $schedule,
        int $debtRatioAfterBps,
        int $remainderToLiveCents,
        ScoreResult $score,
    ): self {
        return new self($request, $today, false, $profile, $offer, $schedule, $debtRatioAfterBps, $remainderToLiveCents, $score);
    }

    public function requireProfile(): ApplicantProfile
    {
        return $this->profile ?? throw new \LogicException('Profil absent : règle PRODUCT évaluée hors contexte produit.');
    }

    public function requireOffer(): OfferConfigDto
    {
        return $this->offer ?? throw new \LogicException('Produit absent : règle PRODUCT évaluée hors contexte produit.');
    }

    public function requireSchedule(): LoanSchedule
    {
        return $this->schedule ?? throw new \LogicException('Échéancier absent : règle PRODUCT évaluée hors contexte produit.');
    }

    public function requireScore(): ScoreResult
    {
        return $this->score ?? throw new \LogicException('Score absent : règle PRODUCT évaluée hors contexte produit.');
    }
}
