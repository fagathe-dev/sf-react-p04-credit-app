<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Request;

use Credit\Core\Enum\CivilityEnum;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\EmploymentStatusEnum;
use Credit\Core\Enum\HousingStatusEnum;
use Credit\Core\Enum\MaritalStatusEnum;
use Credit\Core\Enum\ProfessionCategoryEnum;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Payload des 8 étapes du tunnel, consentements et acquisition (POST /api/v1/compare).
 *
 * Le format est contrôlé ici ; les règles de recevabilité (territoire, âge, consentements)
 * sont des règles de scoring de l'étape ADMISSION (Scoring\Rule).
 */
final readonly class ComparisonRequestDto
{
    public const string MOBILE_PATTERN = '/^0[67]\d{8}$/';

    public function __construct(
        // Étape 1 : projet
        public CreditProjectTypeEnum $projectType,
        #[Assert\Range(notInRangeMessage: 'Le montant doit être compris entre 300 € et 100 000 €.', min: 30_000, max: 10_000_000)]
        public int $requestedAmountCents,
        #[Assert\Range(notInRangeMessage: 'La durée doit être comprise entre 6 et 120 mois.', min: 6, max: 120)]
        public int $durationMonths,

        // Étape 2 : famille
        public MaritalStatusEnum $maritalStatus,
        public bool $hasCoBorrower,
        #[Assert\Range(min: 0, max: 15)]
        public int $dependentChildrenCount,

        // Étape 3 : logement
        public HousingStatusEnum $housingStatus,
        #[Assert\NotBlank(message: 'Le code postal est obligatoire.')]
        #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit comporter 5 chiffres.')]
        public string $postalCode,
        #[Assert\NotBlank(message: 'La ville est obligatoire.')]
        #[Assert\Length(max: 150)]
        public string $city,

        // Étape 4 : emploi
        public EmploymentStatusEnum $employmentStatus,
        public ProfessionCategoryEnum $professionCategory,

        // Étapes 5 et 6 : revenus et charges
        #[Assert\Valid]
        public IncomesInputDto $incomes,
        #[Assert\Valid]
        public ChargesInputDto $charges,

        // Étape 7 : identité
        public CivilityEnum $civility,
        #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
        #[Assert\Length(max: 100)]
        public string $firstName,
        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Length(max: 100)]
        public string $lastName,
        #[Assert\NotBlank(message: 'La date de naissance est obligatoire.')]
        #[Assert\Date(message: 'La date de naissance est invalide.')]
        public string $birthDate,

        // Étape 8 : contact et consentements
        #[Assert\NotBlank(message: 'L\'adresse e-mail est obligatoire.')]
        #[Assert\Email(message: 'L\'adresse e-mail est invalide.')]
        #[Assert\Length(max: 180)]
        public string $email,
        #[Assert\NotBlank(message: 'Le numéro de mobile est obligatoire.')]
        #[Assert\Regex(pattern: self::MOBILE_PATTERN, message: 'Le numéro de mobile doit commencer par 06 ou 07 et comporter 10 chiffres.')]
        public string $phone,
        public bool $consentPartnerTransmission = false,
        public bool $consentAdvisorCallback = false,
        public bool $consentClientSpaceCreation = false,

        // Champs optionnels
        #[Assert\Date(message: 'La date d\'entrée dans le logement est invalide.')]
        public ?string $housingSince = null,
        #[Assert\Date(message: 'La date de début de contrat est invalide.')]
        public ?string $contractStartDate = null,
        /** Période d'essai terminée (salariés) : information transmise au conseiller, non utilisée par le scoring. */
        public ?bool $trialPeriodCompleted = null,
        /** Nom de naissance (nom de jeune fille) : obligatoire si requiresBirthName(), non utilisé par le scoring. */
        #[Assert\Length(max: 100)]
        public ?string $birthName = null,
        #[Assert\Valid]
        public ?CoBorrowerInputDto $coBorrower = null,
        #[Assert\Valid]
        public ?AcquisitionInputDto $acquisition = null,
    ) {}

    #[Assert\IsTrue(message: 'Les informations du co-emprunteur sont obligatoires pour emprunter à deux.')]
    public function isCoBorrowerConsistent(): bool
    {
        return !$this->hasCoBorrower || null !== $this->coBorrower;
    }

    /**
     * Nom de naissance demandé aux femmes mariées (le nom d'usage peut être celui du conjoint).
     * La situation familiale est celle du foyer : la règle vaut aussi pour la co-emprunteuse.
     */
    public static function requiresBirthName(?CivilityEnum $civility, MaritalStatusEnum $maritalStatus): bool
    {
        return CivilityEnum::MRS === $civility && MaritalStatusEnum::MARRIED === $maritalStatus;
    }

    #[Assert\Callback]
    public function validateBirthNames(ExecutionContextInterface $context): void
    {
        if (self::requiresBirthName($this->civility, $this->maritalStatus) && '' === trim($this->birthName ?? '')) {
            $context->buildViolation('Le nom de naissance est obligatoire.')->atPath('birthName')->addViolation();
        }

        $coBorrower = $this->coBorrower;
        if ($this->hasCoBorrower && null !== $coBorrower && self::requiresBirthName($coBorrower->civility, $this->maritalStatus) && '' === trim($coBorrower->birthName ?? '')) {
            $context->buildViolation('Le nom de naissance de la co-emprunteuse est obligatoire.')->atPath('coBorrower.birthName')->addViolation();
        }
    }

    public function normalizedEmail(): string
    {
        return mb_strtolower(trim($this->email));
    }
}
