<?php

declare(strict_types=1);

namespace App\Tests\Crm\Priority;

use App\Crm\Priority\CallbackPriorityResolver;
use App\Crm\Priority\Rule\HotAdjustableRule;
use App\Crm\Priority\Rule\LargeAmountHesitantRule;
use App\Crm\Priority\Rule\LastReminderBeforeExpiryRule;
use App\Crm\Priority\Rule\StandardQuoteReminderRule;
use App\Entity\Advisor;
use App\Entity\Client;
use App\Entity\Dossier;
use App\Enum\CallbackPriorityEnum;
use Credit\Core\Catalog\InMemoryProductCatalog;
use Credit\Core\Catalog\Partner\AltisFinancePartner;
use Credit\Core\Catalog\Partner\CreditNovaPartner;
use Credit\Core\Catalog\Partner\FlashPactPartner;
use Credit\Core\Catalog\Partner\SolvaBankPartner;
use Credit\Core\Catalog\Partner\VeloceCreditPartner;
use Credit\Core\CreditCoreBundle;
use Credit\Core\Enum\CallbackStatusEnum;
use Credit\Core\Enum\CivilityEnum;
use Credit\Core\Enum\DossierStatusEnum;
use Credit\Core\Enum\ScoringDecisionEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Bornes des règles RG-01 à RG-04 et garde commune (consentement, statut, suivi clos).
 */
final class CallbackPriorityResolverTest extends TestCase
{
    private const int HOUR = 3_600;
    private const int DAY = 86_400;

    private static ?CallbackPriorityResolver $resolver = null;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-11-06 12:00:00');
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: CallbackPriorityEnum}>
     */
    public static function cases(): iterable
    {
        // RG-01 : zone grise 33–40 %, < 72 h, blindage
        yield 'P1 à 36 %' => [['debt' => 3_600], CallbackPriorityEnum::P1];
        yield 'P1 à 33,01 % (zone 33–35 rattachée)' => [['debt' => 3_301, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::P1];
        yield 'P1 à 40,00 % inclus' => [['debt' => 4_000], CallbackPriorityEnum::P1];
        yield 'au-delà de 40 % : hors P1' => [['debt' => 4_001], CallbackPriorityEnum::NONE];
        yield 'P1 à 71 h 59' => [['age' => 72 * self::HOUR - 60], CallbackPriorityEnum::P1];
        yield 'P1 expiré à 72 h' => [['age' => 72 * self::HOUR], CallbackPriorityEnum::NONE];
        yield 'reste à vivre < 850 €' => [['remainder' => 84_999], CallbackPriorityEnum::NONE];
        yield 'revenus < 1 500 €' => [['income' => 149_999], CallbackPriorityEnum::NONE];
        yield 'durée déjà au maximum du produit' => [['duration' => 72, 'reference' => 'ALTIS_AUTO_PREMIUM'], CallbackPriorityEnum::NONE];

        // RG-02 : ≥ 15 000 €, ≤ 33 %, 2 h à 36 h
        yield 'P1 bis à 2 h' => [['debt' => 2_800, 'amount' => 1_500_000, 'age' => 2 * self::HOUR, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::P1_BIS];
        yield 'P1 bis trop tôt (1 h 59)' => [['debt' => 2_800, 'amount' => 1_500_000, 'age' => 2 * self::HOUR - 60, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::NONE];
        yield 'P1 bis à 36 h' => [['debt' => 3_300, 'amount' => 2_000_000, 'age' => 36 * self::HOUR, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::P1_BIS];
        yield 'gros montant entre 36 h et 5 j : rien' => [['debt' => 2_800, 'amount' => 2_000_000, 'age' => 40 * self::HOUR, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::NONE];

        // RG-03 : < 15 000 €, ≤ 33 %, 24 h à 48 h (fenêtre stricte)
        yield 'P2 à 24 h' => [['debt' => 2_500, 'amount' => 1_499_900, 'age' => 24 * self::HOUR, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::P2];
        yield 'P2 à 48 h' => [['debt' => 2_500, 'amount' => 800_000, 'age' => 48 * self::HOUR, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::P2];
        yield 'devis standard à 3 j : rien' => [['debt' => 2_500, 'amount' => 800_000, 'age' => 3 * self::DAY, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::NONE];

        // RG-04 : offres non choisies, > 5 j et avant expiration (7 j)
        yield 'P3 à 5 j + 1 min' => [['debt' => 2_500, 'amount' => 800_000, 'age' => 5 * self::DAY + 60, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::P3];
        yield 'offres expirées à 7 j' => [['debt' => 2_500, 'amount' => 800_000, 'age' => 7 * self::DAY, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS], CallbackPriorityEnum::NONE];

        // Garde commune
        yield 'sans consentement de rappel' => [['consent' => false], CallbackPriorityEnum::NONE];
        yield 'dossier en attente de vérification' => [['pending' => true], CallbackPriorityEnum::NONE];
        yield 'suivi clos' => [['callback' => CallbackStatusEnum::CLOSED], CallbackPriorityEnum::NONE];
        yield 'pas de réponse : reste prioritaire' => [['callback' => CallbackStatusEnum::NO_ANSWER], CallbackPriorityEnum::P1];
        yield 'refus ferme' => [['decision' => ScoringDecisionEnum::HARD_REJECT], CallbackPriorityEnum::NONE];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('cases')]
    public function testPriority(array $overrides, CallbackPriorityEnum $expected): void
    {
        $assessment = self::resolver()->assess($this->dossier($overrides), $this->now);

        self::assertSame($expected, $assessment->priority, $assessment->reason);
        self::assertNotSame('', $assessment->reason);
    }

    public function testSubscribedDossierLeavesTheQueue(): void
    {
        $dossier = $this->dossier(['debt' => 2_500, 'amount' => 800_000, 'age' => 30 * self::HOUR, 'decision' => ScoringDecisionEnum::INSTANT_OFFERS]);
        self::assertSame(CallbackPriorityEnum::P2, self::resolver()->assess($dossier, $this->now)->priority);

        $dossier->subscribe('NOVA_AUTO_OCCASION', $this->now);
        self::assertSame(CallbackPriorityEnum::NONE, self::resolver()->assess($dossier, $this->now)->priority);
    }

    /**
     * Dossier par défaut : P1 (prêt auto 15 000 € sur 48 mois, 36 %, 10 h, reste à vivre et revenus suffisants).
     *
     * @param array<string, mixed> $o
     */
    private function dossier(array $o): Dossier
    {
        $client = (new Client('client@example.com'))
            ->setCivility(CivilityEnum::MRS)->setFirstName('Camille')->setLastName('Martin')
            ->setBirthDate(new \DateTimeImmutable('1988-04-12'))->setPhone('0612345678');

        $decision = $o['decision'] ?? ScoringDecisionEnum::ADVISOR_CALLBACK_NEEDED;
        $dossier = (new Dossier($client, ($o['pending'] ?? false) ? DossierStatusEnum::PENDING_VERIFICATION : DossierStatusEnum::ACTIVE, $decision))
            ->setRequestSnapshot(['projectType' => 'auto', 'requestedAmountCents' => $o['amount'] ?? 1_500_000, 'durationMonths' => $o['duration'] ?? 48])
            ->setScoringSummary([
                'debtRatioAfterBps' => $o['debt'] ?? 3_600,
                'remainderToLiveCents' => $o['remainder'] ?? 120_000,
                'weightedIncomeCents' => $o['income'] ?? 300_000,
                'referenceOfferCode' => $o['reference'] ?? 'ALTIS_AUTO_PREMIUM',
            ])
            ->setQuotes(ScoringDecisionEnum::INSTANT_OFFERS === $decision ? [['offerCode' => 'NOVA_AUTO_OCCASION']] : [])
            ->setConsentAdvisorCallback($o['consent'] ?? true)
            ->backdate($this->now->modify(\sprintf('-%d seconds', $o['age'] ?? 10 * self::HOUR)));

        if (isset($o['callback'])) {
            $dossier->recordCallbackFollowUp(new Advisor('conseil@example.com', 'Alex', 'Durand'), $o['callback'], null, $this->now);
        }

        return $dossier;
    }

    private static function resolver(): CallbackPriorityResolver
    {
        if (null === self::$resolver) {
            $dataDir = \dirname((new \ReflectionClass(CreditCoreBundle::class))->getFileName() ?: '', 2) . '/config/data';
            $catalog = new InMemoryProductCatalog([
                new AltisFinancePartner($dataDir),
                new CreditNovaPartner($dataDir),
                new FlashPactPartner($dataDir),
                new VeloceCreditPartner($dataDir),
                new SolvaBankPartner($dataDir),
            ]);

            self::$resolver = new CallbackPriorityResolver([
                new LastReminderBeforeExpiryRule(),
                new HotAdjustableRule($catalog),
                new StandardQuoteReminderRule(),
                new LargeAmountHesitantRule(),
            ]);
        }

        return self::$resolver;
    }
}
