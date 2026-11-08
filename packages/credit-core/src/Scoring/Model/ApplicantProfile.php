<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Model;

use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\EmploymentStatusEnum;
use Credit\Core\Enum\HousingStatusEnum;

/**
 * Profil financier de l'Applicant, indépendant du produit, du montant et de la durée.
 */
final readonly class ApplicantProfile
{
    public function __construct(
        public CreditProjectTypeEnum $projectType,
        public int $requestedAmountCents,
        public int $requestedDurationMonths,
        /** Revenus pondérés du foyer (revenus locatifs à 70 %). */
        public int $weightedIncomeCents,
        public int $existingChargesCents,
        public int $debtRatioBeforeBps,
        /** Seuil minimal de reste à vivre du foyer (adultes et enfants). */
        public int $householdMinRemainderCents,
        public EmploymentStatusEnum $employmentStatus,
        public int $seniorityMonths,
        public HousingStatusEnum $housingStatus,
        public bool $hasCoBorrower,
        public int $dependentChildrenCount,
    ) {}
}
