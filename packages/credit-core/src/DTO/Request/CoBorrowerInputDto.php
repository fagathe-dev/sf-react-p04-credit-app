<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Request;

use Credit\Core\Enum\CivilityEnum;
use Credit\Core\Enum\EmploymentStatusEnum;
use Credit\Core\Enum\ProfessionCategoryEnum;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Co-emprunteur : situation professionnelle (étape 4) et identité (étape 7).
 * Il n'a pas de compte : ses informations restent dans le dossier.
 */
final readonly class CoBorrowerInputDto
{
    public function __construct(
        public EmploymentStatusEnum $employmentStatus,
        public ProfessionCategoryEnum $professionCategory,
        #[Assert\Date(message: 'La date de début de contrat est invalide.')]
        public ?string $contractStartDate = null,
        public ?CivilityEnum $civility = null,
        #[Assert\Length(max: 100)]
        public ?string $firstName = null,
        #[Assert\Length(max: 100)]
        public ?string $lastName = null,
        #[Assert\NotBlank(message: 'La date de naissance du co-emprunteur est obligatoire.')]
        #[Assert\Date(message: 'La date de naissance du co-emprunteur est invalide.')]
        public string $birthDate = '',
        /** Période d'essai terminée (salariés) : information transmise au conseiller, non utilisée par le scoring. */
        public ?bool $trialPeriodCompleted = null,
        /** Nom de naissance : obligatoire selon ComparisonRequestDto::requiresBirthName(). */
        #[Assert\Length(max: 100)]
        public ?string $birthName = null,
    ) {}
}
