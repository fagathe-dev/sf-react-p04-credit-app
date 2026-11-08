<?php

declare(strict_types=1);

namespace Credit\Core\Tests\Scoring;

use Credit\Core\DTO\Request\ChargesInputDto;
use Credit\Core\DTO\Request\IncomesInputDto;
use Credit\Core\Enum\AdjustmentKindEnum;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\EmploymentStatusEnum;
use Credit\Core\Enum\RiskGradeEnum;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Enum\ScoringDecisionEnum;
use Credit\Core\Tests\CoreFactory;
use PHPUnit\Framework\TestCase;

final class CreditComparatorTest extends TestCase
{
    public function testSolidProfileGetsInstantOffersSortedByApr(): void
    {
        $result = CoreFactory::comparator()->compare(CoreFactory::request(), CoreFactory::today());

        self::assertSame(ScoringDecisionEnum::INSTANT_OFFERS, $result->decision);
        self::assertNotEmpty($result->quotes);
        self::assertTrue($result->quotes[0]->isBestRate);
        self::assertStringStartsWith('http://api.test/assets/logos/', $result->quotes[0]->partnerLogo);

        $aprs = array_map(static fn ($quote) => $quote->aprBps, $result->quotes);
        $sorted = $aprs;
        sort($sorted);
        self::assertSame($sorted, $aprs);

        foreach ($result->quotes as $index => $quote) {
            self::assertSame(1_500_000, $quote->amountCents);
            self::assertSame(48, $quote->durationMonths);
            self::assertSame(0 === $index, $quote->isBestRate);
        }

        self::assertSame(RiskGradeEnum::GRADE_A, $result->scoringSummary->riskGrade);
        self::assertNull($result->advisorFallback);
        self::assertNull($result->rejection);
    }

    public function testTooHighDebtRatioLeadsToAdvisorCallbackWithSuggestions(): void
    {
        // 2 000 € de revenus, 600 € de charges : 30 000 € sur 24 mois sont trop lourds
        $request = CoreFactory::request([
            'requestedAmountCents' => 3_000_000,
            'durationMonths' => 24,
            'incomes' => new IncomesInputDto(netMonthlySalaryCents: 200_000),
            'charges' => new ChargesInputDto(rentMonthlyCents: 60_000),
        ]);

        $result = CoreFactory::comparator()->compare($request, CoreFactory::today());

        self::assertSame(ScoringDecisionEnum::ADVISOR_CALLBACK_NEEDED, $result->decision);
        self::assertSame([], $result->quotes);
        self::assertNotNull($result->advisorFallback);
        self::assertNotEmpty($result->advisorFallback->suggestions);
        self::assertLessThanOrEqual(3, \count($result->advisorFallback->suggestions));
        self::assertNotEmpty($result->advisorFallback->reasons);
        self::assertTrue($result->advisorFallback->callbackRequested);
        self::assertStringContainsString('06 12 34 56 78', $result->advisorFallback->message);

        $previous = -1;
        foreach ($result->advisorFallback->suggestions as $suggestion) {
            self::assertLessThanOrEqual(3_000_000, $suggestion->amountCents);
            self::assertGreaterThanOrEqual(24, $suggestion->durationMonths);
            self::assertGreaterThanOrEqual($previous, $suggestion->distanceBps);
            $previous = $suggestion->distanceBps;

            if (AdjustmentKindEnum::LONGER_DURATION === $suggestion->kind) {
                self::assertSame(3_000_000, $suggestion->amountCents);
            }
            if (AdjustmentKindEnum::LOWER_AMOUNT === $suggestion->kind) {
                self::assertSame(24, $suggestion->durationMonths);
            }
        }
    }

    public function testUnemployedApplicantIsHardRejected(): void
    {
        $request = CoreFactory::request([
            'employmentStatus' => EmploymentStatusEnum::UNEMPLOYED,
            'contractStartDate' => null,
        ]);

        $result = CoreFactory::comparator()->compare($request, CoreFactory::today());

        self::assertSame(ScoringDecisionEnum::HARD_REJECT, $result->decision);
        self::assertNotNull($result->rejection);
        self::assertContains('Aucun de nos partenaires n\'accepte votre type de contrat pour ce projet.', $result->rejection->reasons);
        self::assertNull($result->advisorFallback);

        foreach ($result->ruleExecutions as $execution) {
            if ('ALLOWED_EMPLOYMENT_STATUS' === $execution->ruleCode) {
                self::assertSame(RuleSeverityEnum::STRUCTURAL, $execution->severity);
            }
        }
    }

    public function testAmountBelowEveryProductMinimumIsStructural(): void
    {
        $request = CoreFactory::request([
            'projectType' => CreditProjectTypeEnum::HOME_IMPROVEMENT,
            'requestedAmountCents' => 100_000,
            'durationMonths' => 24,
        ]);

        $result = CoreFactory::comparator()->compare($request, CoreFactory::today());

        self::assertSame(ScoringDecisionEnum::HARD_REJECT, $result->decision);
        self::assertContains('Le montant demandé est inférieur au minimum proposé par nos partenaires pour ce projet.', $result->rejection?->reasons ?? []);
    }

    public function testAdmissionRules(): void
    {
        $engine = CoreFactory::engine();
        $today = CoreFactory::today();

        self::assertSame([], $engine->checkAdmission(CoreFactory::request(), true, $today));

        $codes = static fn (array $failures): array => array_map(static fn ($f) => $f->ruleCode, $failures);

        self::assertSame(['METROPOLITAN_TERRITORY'], $codes($engine->checkAdmission(CoreFactory::request(['postalCode' => '97400']), false, $today)));
        self::assertSame(['METROPOLITAN_TERRITORY'], $codes($engine->checkAdmission(CoreFactory::request(['postalCode' => '98000']), false, $today)));
        self::assertSame([], $engine->checkAdmission(CoreFactory::request(['postalCode' => '20000']), false, $today));
        self::assertSame(['LEGAL_MAJORITY_AGE'], $codes($engine->checkAdmission(CoreFactory::request(['birthDate' => '2009-01-01']), false, $today)));
        self::assertSame(['LEGAL_MAJORITY_AGE'], $codes($engine->checkAdmission(CoreFactory::request(['birthDate' => '1945-01-01']), false, $today)));
        self::assertSame(['MANDATORY_CONSENT'], $codes($engine->checkAdmission(CoreFactory::request(['consentPartnerTransmission' => false]), false, $today)));

        // Consentement de création d'espace client : exigé pour un nouveau client seulement
        $withoutSpaceConsent = CoreFactory::request(['consentClientSpaceCreation' => false]);
        self::assertSame(['MANDATORY_CONSENT'], $codes($engine->checkAdmission($withoutSpaceConsent, true, $today)));
        self::assertSame([], $engine->checkAdmission($withoutSpaceConsent, false, $today));
    }

    public function testScoreGrid(): void
    {
        $engine = CoreFactory::engine();
        $profile = $engine->buildProfile(CoreFactory::request(), CoreFactory::today());

        // CDI +200, ancienneté ≥ 36 mois +100, locataire +20, endettement ≤ 25 % +100
        self::assertSame(920, $engine->score($profile, 2_500)->score);
        // Endettement > 35 % : −300 au lieu de +100
        self::assertSame(520, $engine->score($profile, 3_600)->score);
        self::assertSame(RiskGradeEnum::GRADE_C, $engine->score($profile, 3_600)->grade);
    }
}
