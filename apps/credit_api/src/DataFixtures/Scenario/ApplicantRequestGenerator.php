<?php

declare(strict_types=1);

namespace App\DataFixtures\Scenario;

use Credit\Core\DTO\Request\AcquisitionInputDto;
use Credit\Core\DTO\Request\ChargesInputDto;
use Credit\Core\DTO\Request\CoBorrowerInputDto;
use Credit\Core\DTO\Request\ComparisonRequestDto;
use Credit\Core\DTO\Request\IncomesInputDto;
use Credit\Core\Enum\CivilityEnum;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\EmploymentStatusEnum;
use Credit\Core\Enum\HousingStatusEnum;
use Credit\Core\Enum\MaritalStatusEnum;
use Credit\Core\Enum\ProfessionCategoryEnum;
use Faker\Generator;

/**
 * Génère des demandes de comparaison réalistes pour les fixtures, orientées par un profil.
 *
 * Garanties : codes postaux de France métropolitaine et Corse (01000–95999), âge 19 à 79 ans,
 * mobiles 06/07, montants en centimes. Le résultat (décision, endettement…) est ensuite calculé
 * par le vrai moteur de scoring : il est mathématiquement cohérent avec ces données.
 */
final class ApplicantRequestGenerator
{
    public const string LARGE_ELIGIBLE = 'large_eligible';
    public const string STANDARD_ELIGIBLE = 'standard_eligible';
    public const string GRAY_ZONE = 'gray_zone';
    public const string REJECT = 'reject';

    /** @var list<array{0: string, 1: string}> code postal, ville */
    private const array CITIES = [
        ['75011', 'Paris'], ['75015', 'Paris'], ['69003', 'Lyon'], ['13008', 'Marseille'], ['31000', 'Toulouse'],
        ['06000', 'Nice'], ['44000', 'Nantes'], ['67000', 'Strasbourg'], ['34000', 'Montpellier'], ['33000', 'Bordeaux'],
        ['59000', 'Lille'], ['35000', 'Rennes'], ['51100', 'Reims'], ['42000', 'Saint-Étienne'], ['83000', 'Toulon'],
        ['38000', 'Grenoble'], ['21000', 'Dijon'], ['49000', 'Angers'], ['37000', 'Tours'], ['63000', 'Clermont-Ferrand'],
        ['87000', 'Limoges'], ['80000', 'Amiens'], ['25000', 'Besançon'], ['66000', 'Perpignan'], ['57000', 'Metz'],
        ['20000', 'Ajaccio'], ['20200', 'Bastia'], ['01000', 'Bourg-en-Bresse'], ['95000', 'Cergy'], ['92100', 'Boulogne-Billancourt'],
        ['93100', 'Montreuil'], ['94000', 'Créteil'], ['64000', 'Pau'], ['29200', 'Brest'], ['72000', 'Le Mans'],
    ];

    private const array UTM = [
        ['google', 'cpc', 'credit_conso'], ['facebook', 'social', 'auto_printemps'], ['newsletter', 'email', 'travaux'],
        ['credit_front', 'seo', 'credit_vert'], ['bing', 'cpc', 'tresorerie'],
    ];

    private const array DURATIONS = [12, 24, 36, 48, 60, 72, 84];

    /** Statuts soumis à une période d'essai. */
    private const array SALARIED = [EmploymentStatusEnum::CDI, EmploymentStatusEnum::CDD, EmploymentStatusEnum::CIVIL_SERVANT];

    public function __construct(
        private readonly Generator $faker,
    ) {}

    /**
     * @param array{email: string, civility: CivilityEnum, firstName: string, lastName: string, birthDate: string, phone: string}|null $identity
     */
    public function generate(string $profile, \DateTimeImmutable $submittedAt, bool $consentAdvisorCallback, ?array $identity = null): ComparisonRequestDto
    {
        $f = $this->faker;

        [$project, $amountEuros, $duration, $incomeEuros, $chargeRatioPercent, $employment] = match ($profile) {
            self::LARGE_ELIGIBLE => [
                $f->randomElement([CreditProjectTypeEnum::AUTO, CreditProjectTypeEnum::HOME_IMPROVEMENT, CreditProjectTypeEnum::GREEN_LOAN]),
                $f->numberBetween(30, 90) * 500,
                $f->randomElement([48, 60, 72]),
                $f->numberBetween(45, 90) * 100,
                $f->numberBetween(3, 15),
                $f->randomElement([EmploymentStatusEnum::CDI, EmploymentStatusEnum::CIVIL_SERVANT, EmploymentStatusEnum::RETIRED]),
            ],
            self::STANDARD_ELIGIBLE => [
                $f->randomElement(CreditProjectTypeEnum::cases()),
                $f->numberBetween(4, 28) * 500,
                $f->randomElement([12, 24, 36, 48]),
                $f->numberBetween(20, 55) * 100,
                $f->numberBetween(3, 18),
                $f->randomElement([EmploymentStatusEnum::CDI, EmploymentStatusEnum::CIVIL_SERVANT, EmploymentStatusEnum::RETIRED, EmploymentStatusEnum::CDD, EmploymentStatusEnum::INDEPENDENT]),
            ],
            self::GRAY_ZONE => [
                $f->randomElement([CreditProjectTypeEnum::AUTO, CreditProjectTypeEnum::HOME_IMPROVEMENT, CreditProjectTypeEnum::PERSONAL_GOODS, CreditProjectTypeEnum::CASH_TREASURY]),
                $f->numberBetween(12, 50) * 500,
                $f->randomElement([24, 36, 48]),
                $f->numberBetween(20, 42) * 100,
                $f->numberBetween(18, 29),
                $f->randomElement([EmploymentStatusEnum::CDI, EmploymentStatusEnum::CIVIL_SERVANT, EmploymentStatusEnum::RETIRED, EmploymentStatusEnum::INDEPENDENT]),
            ],
            self::REJECT => $this->rejectProfile(),
            default => throw new \InvalidArgumentException(\sprintf('Profil inconnu : %s', $profile)),
        };

        $hasCoBorrower = $f->boolean(25);
        $children = $f->randomElement([0, 0, 0, 1, 1, 2, 3]);
        $housing = $f->randomElement([HousingStatusEnum::TENANT, HousingStatusEnum::TENANT, HousingStatusEnum::OWNER_WITH_MORTGAGE, HousingStatusEnum::OWNER_WITHOUT_MORTGAGE, HousingStatusEnum::HOSTED_FREE]);
        $incomeCents = $incomeEuros * 100;
        $chargesCents = intdiv($incomeCents * $chargeRatioPercent, 100);

        // Revenus : salaire principal, co-emprunteur, éventuels revenus annexes (locatif retenu à 70 % par le moteur)
        $rental = $f->boolean(10) ? $f->numberBetween(3, 9) * 10_000 : 0;
        $allowances = $children > 0 && $f->boolean(40) ? $f->numberBetween(1, 4) * 5_000 : 0;
        $salaries = max(50_000, $incomeCents - $allowances - intdiv($rental * 7, 10));
        $coSalary = $hasCoBorrower ? intdiv($salaries * $f->numberBetween(30, 50), 100) : 0;

        // Charges : logement d'abord, puis crédits en cours
        $consumerLoans = $f->boolean(30) ? min($chargesCents, $f->numberBetween(5, 30) * 1_000) : 0;
        $housingCharge = max(0, $chargesCents - $consumerLoans);

        $city = $f->randomElement(self::CITIES);
        // Environ 5 % de civilités « Autre »
        $civility = $identity['civility'] ?? ($f->boolean(5) ? CivilityEnum::OTHER : ($f->boolean() ? CivilityEnum::MR : CivilityEnum::MRS));
        $gender = match ($civility) {
            CivilityEnum::MR => 'male',
            CivilityEnum::MRS => 'female',
            CivilityEnum::OTHER => $f->randomElement(['male', 'female']),
        };
        $coCivility = CivilityEnum::MR === $civility ? CivilityEnum::MRS : CivilityEnum::MR;
        $maritalStatus = $hasCoBorrower
            ? $f->randomElement([MaritalStatusEnum::MARRIED, MaritalStatusEnum::PACS])
            : $f->randomElement([MaritalStatusEnum::SINGLE, MaritalStatusEnum::MARRIED, MaritalStatusEnum::DIVORCED, MaritalStatusEnum::WIDOWED, MaritalStatusEnum::SINGLE]);
        $utm = $f->boolean(60) ? $f->randomElement(self::UTM) : null;

        return new ComparisonRequestDto(
            projectType: $project,
            requestedAmountCents: $amountEuros * 100,
            durationMonths: \in_array($duration, self::DURATIONS, true) ? $duration : 48,
            maritalStatus: $maritalStatus,
            hasCoBorrower: $hasCoBorrower,
            dependentChildrenCount: $children,
            housingStatus: $housing,
            postalCode: $city[0],
            city: $city[1],
            employmentStatus: $employment,
            professionCategory: $this->professionFor($employment),
            incomes: new IncomesInputDto(
                netMonthlySalaryCents: $salaries - $coSalary,
                coBorrowerNetSalaryCents: $coSalary,
                familyAllowancesCents: $allowances,
                rentalIncomeGrossCents: $rental,
            ),
            charges: new ChargesInputDto(
                rentMonthlyCents: HousingStatusEnum::TENANT === $housing ? $housingCharge : 0,
                mortgageMonthlyCents: HousingStatusEnum::OWNER_WITH_MORTGAGE === $housing ? $housingCharge : 0,
                consumerLoansMonthlyCents: $consumerLoans,
                otherChargesMonthlyCents: \in_array($housing, [HousingStatusEnum::TENANT, HousingStatusEnum::OWNER_WITH_MORTGAGE], true) ? 0 : $housingCharge,
            ),
            civility: $civility,
            firstName: $identity['firstName'] ?? $f->firstName($gender),
            lastName: $identity['lastName'] ?? $f->lastName(),
            birthDate: $identity['birthDate'] ?? $this->birthDate($submittedAt),
            email: $identity['email'] ?? $f->unique()->safeEmail(),
            phone: $identity['phone'] ?? $f->randomElement(['06', '07']) . $f->numerify('########'),
            consentPartnerTransmission: true,
            consentAdvisorCallback: $consentAdvisorCallback,
            consentClientSpaceCreation: true,
            housingSince: $submittedAt->modify(\sprintf('-%d months', $f->numberBetween(6, 180)))->format('Y-m-01'),
            contractStartDate: \in_array($employment, [EmploymentStatusEnum::UNEMPLOYED, EmploymentStatusEnum::STUDENT], true)
                ? null
                : $submittedAt->modify(\sprintf('-%d months', $f->numberBetween(13, 240)))->format('Y-m-01'),
            // Contrats de plus d'un an : période d'essai terminée pour les salariés
            trialPeriodCompleted: \in_array($employment, self::SALARIED, true) ? true : null,
            birthName: ComparisonRequestDto::requiresBirthName($civility, $maritalStatus) ? $f->lastName() : null,
            coBorrower: $hasCoBorrower ? new CoBorrowerInputDto(
                employmentStatus: $f->randomElement([EmploymentStatusEnum::CDI, EmploymentStatusEnum::CIVIL_SERVANT, EmploymentStatusEnum::CDD]),
                professionCategory: $f->randomElement([ProfessionCategoryEnum::EMPLOYEE, ProfessionCategoryEnum::INTERMEDIATE, ProfessionCategoryEnum::EXECUTIVE]),
                contractStartDate: $submittedAt->modify(\sprintf('-%d months', $f->numberBetween(6, 200)))->format('Y-m-01'),
                civility: $coCivility,
                firstName: $f->firstName(CivilityEnum::MR === $coCivility ? 'male' : 'female'),
                lastName: $f->lastName(),
                birthDate: $this->birthDate($submittedAt),
                trialPeriodCompleted: true,
                birthName: ComparisonRequestDto::requiresBirthName($coCivility, $maritalStatus) ? $f->lastName() : null,
            ) : null,
            acquisition: null === $utm ? null : new AcquisitionInputDto($utm[0], $utm[1], $utm[2], null),
        );
    }

    /**
     * Profils refusés : sans emploi, endettement déjà très élevé, ou montant sous le minimum des produits.
     *
     * @return array{0: CreditProjectTypeEnum, 1: int, 2: int, 3: int, 4: int, 5: EmploymentStatusEnum}
     */
    private function rejectProfile(): array
    {
        $f = $this->faker;

        return match ($f->numberBetween(1, 3)) {
            1 => [$f->randomElement(CreditProjectTypeEnum::cases()), $f->numberBetween(4, 30) * 500, $f->randomElement([24, 36, 48]), $f->numberBetween(9, 20) * 100, $f->numberBetween(10, 30), EmploymentStatusEnum::UNEMPLOYED],
            2 => [$f->randomElement(CreditProjectTypeEnum::cases()), $f->numberBetween(10, 40) * 500, $f->randomElement([36, 48, 60]), $f->numberBetween(16, 30) * 100, $f->numberBetween(42, 60), $f->randomElement([EmploymentStatusEnum::CDI, EmploymentStatusEnum::CDD])],
            default => [CreditProjectTypeEnum::HOME_IMPROVEMENT, $f->numberBetween(4, 9) * 100, 24, $f->numberBetween(20, 40) * 100, $f->numberBetween(5, 20), EmploymentStatusEnum::CDI],
        };
    }

    private function professionFor(EmploymentStatusEnum $status): ProfessionCategoryEnum
    {
        return match ($status) {
            EmploymentStatusEnum::INDEPENDENT => ProfessionCategoryEnum::SELF_EMPLOYED,
            EmploymentStatusEnum::RETIRED, EmploymentStatusEnum::UNEMPLOYED, EmploymentStatusEnum::STUDENT => ProfessionCategoryEnum::INACTIVE,
            default => $this->faker->randomElement([ProfessionCategoryEnum::EMPLOYEE, ProfessionCategoryEnum::EXECUTIVE, ProfessionCategoryEnum::INTERMEDIATE, ProfessionCategoryEnum::WORKER]),
        };
    }

    /** Âge strictement compris entre 19 et 79 ans à la date du résultat. */
    private function birthDate(\DateTimeImmutable $at): string
    {
        return $at->modify(\sprintf('-%d years', $this->faker->numberBetween(19, 78)))
            ->modify(\sprintf('-%d days', $this->faker->numberBetween(0, 360)))
            ->format('Y-m-d');
    }
}
