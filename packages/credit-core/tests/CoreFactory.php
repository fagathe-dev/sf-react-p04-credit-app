<?php

declare(strict_types=1);

namespace Credit\Core\Tests;

use Credit\Core\Catalog\InMemoryProductCatalog;
use Credit\Core\Catalog\Partner\AltisFinancePartner;
use Credit\Core\Catalog\Partner\CreditNovaPartner;
use Credit\Core\Catalog\Partner\FlashPactPartner;
use Credit\Core\Catalog\Partner\SolvaBankPartner;
use Credit\Core\Catalog\Partner\VeloceCreditPartner;
use Credit\Core\Catalog\PartnerLogoUrlResolver;
use Credit\Core\DTO\Request\ChargesInputDto;
use Credit\Core\DTO\Request\ComparisonRequestDto;
use Credit\Core\DTO\Request\IncomesInputDto;
use Credit\Core\Enum\CivilityEnum;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\EmploymentStatusEnum;
use Credit\Core\Enum\HousingStatusEnum;
use Credit\Core\Enum\MaritalStatusEnum;
use Credit\Core\Enum\ProfessionCategoryEnum;
use Credit\Core\Scoring\Calculator\DebtRatioCalculator;
use Credit\Core\Scoring\Calculator\LoanMathCalculator;
use Credit\Core\Scoring\CreditComparator;
use Credit\Core\Scoring\FinancingAdjustmentSimulator;
use Credit\Core\Scoring\InstantQuoteMatcher;
use Credit\Core\Scoring\QuoteFactory;
use Credit\Core\Scoring\Rule;
use Credit\Core\Scoring\Score;
use Credit\Core\Scoring\ScoringEngine;

/**
 * Assemble les services du package sans conteneur (tests unitaires).
 */
final class CoreFactory
{
    public static function catalog(): InMemoryProductCatalog
    {
        $dir = \dirname(__DIR__) . '/config/data';

        return new InMemoryProductCatalog([
            new AltisFinancePartner($dir),
            new CreditNovaPartner($dir),
            new FlashPactPartner($dir),
            new VeloceCreditPartner($dir),
            new SolvaBankPartner($dir),
        ]);
    }

    public static function engine(): ScoringEngine
    {
        return new ScoringEngine(
            [
                new Rule\MetropolitanTerritoryRule(),
                new Rule\LegalMajorityAgeRule(),
                new Rule\MandatoryConsentRule(),
                new Rule\AllowedEmploymentStatusRule(),
                new Rule\EmploymentStabilityRule(),
                new Rule\MinMonthlyIncomeRule(),
                new Rule\ProductAmountRangeRule(),
                new Rule\ProductDurationRangeRule(),
                new Rule\MaxDebtRatioRule(),
                new Rule\RemainderToLiveRule(),
                new Rule\ProductMinRemainderToLiveRule(),
                new Rule\MinRiskGradeRule(),
            ],
            [
                new Score\EmploymentStatusCriterion(),
                new Score\SeniorityCriterion(),
                new Score\HousingCriterion(),
                new Score\ProjectedDebtRatioCriterion(),
            ],
            new LoanMathCalculator(),
            new DebtRatioCalculator(),
        );
    }

    public static function comparator(): CreditComparator
    {
        $engine = self::engine();
        $quotes = new QuoteFactory(new PartnerLogoUrlResolver('http://api.test'));

        return new CreditComparator(
            self::catalog(),
            $engine,
            new InstantQuoteMatcher($engine, $quotes),
            new FinancingAdjustmentSimulator($engine, $quotes),
            new DebtRatioCalculator(),
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function request(array $overrides = []): ComparisonRequestDto
    {
        $values = array_merge([
            'projectType' => CreditProjectTypeEnum::AUTO,
            'requestedAmountCents' => 1_500_000,
            'durationMonths' => 48,
            'maritalStatus' => MaritalStatusEnum::SINGLE,
            'hasCoBorrower' => false,
            'dependentChildrenCount' => 0,
            'housingStatus' => HousingStatusEnum::TENANT,
            'postalCode' => '75011',
            'city' => 'Paris',
            'employmentStatus' => EmploymentStatusEnum::CDI,
            'professionCategory' => ProfessionCategoryEnum::EXECUTIVE,
            'incomes' => new IncomesInputDto(netMonthlySalaryCents: 450_000),
            'charges' => new ChargesInputDto(rentMonthlyCents: 80_000),
            'civility' => CivilityEnum::MRS,
            'firstName' => 'Camille',
            'lastName' => 'Martin',
            'birthDate' => '1988-04-12',
            'email' => 'camille@example.com',
            'phone' => '0612345678',
            'consentPartnerTransmission' => true,
            'consentAdvisorCallback' => true,
            'consentClientSpaceCreation' => true,
            'contractStartDate' => '2018-01-01',
        ], $overrides);

        return new ComparisonRequestDto(...$values);
    }

    public static function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-11-05');
    }
}
