<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\ClientSpace\DossierFactory;
use App\Crm\Priority\CallbackPriorityResolver;
use App\Crm\Priority\PriorityAssessment;
use App\DataFixtures\Scenario\ApplicantRequestGenerator as Gen;
use App\DataFixtures\Scenario\FixtureBucket as Bucket;
use App\Entity\Advisor;
use App\Entity\Client;
use App\Entity\Dossier;
use App\Entity\EmailNotification;
use App\Enum\CallbackPriorityEnum;
use App\Enum\DossierWorkflowStatusEnum;
use App\Enum\EmailNotificationStatusEnum;
use App\Enum\EmailNotificationTypeEnum;
use App\Notification\DossierMailFactory;
use App\Notification\Handler\DossierMailHandlerInterface;
use Credit\Core\Enum\CallbackStatusEnum;
use Credit\Core\Enum\DossierStatusEnum;
use Credit\Core\Enum\ScoringDecisionEnum;
use Credit\Core\Scoring\CreditComparator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Jeu de données de démonstration : 3 conseillers, 210 dossiers couvrant tous les cas de figure
 * (priorités P1 à P3, souscriptions, validations, refus, file conseiller, vérification en attente).
 *
 * Chaque demande est générée puis passée dans le vrai moteur (CreditComparator) : décision, endettement,
 * reste à vivre et devis stockés sont exactement ceux du calcul. Les dossiers sont antidatés par rapport
 * à l'heure du chargement, et leur priorité est vérifiée par le vrai CallbackPriorityResolver.
 *
 *   php bin/console doctrine:fixtures:load -n
 */
final class AppFixtures extends Fixture
{
    private const int SEED = 20_261_106;
    private const int MAX_ATTEMPTS = 600;
    private const int HOUR = 3_600;
    private const int DAY = 86_400;

    /** @var array<string, DossierMailHandlerInterface> */
    private array $mailHandlers = [];

    /**
     * @param iterable<DossierMailHandlerInterface> $mailHandlers
     */
    public function __construct(
        private readonly CreditComparator $comparator,
        private readonly DossierFactory $dossierFactory,
        private readonly CallbackPriorityResolver $priorityResolver,
        private readonly DossierMailFactory $mailFactory,
        #[AutowireIterator(DossierMailHandlerInterface::TAG)]
        iterable $mailHandlers,
    ) {
        foreach ($mailHandlers as $handler) {
            $this->mailHandlers[$handler->getType()->value] = $handler;
        }
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(self::SEED);
        $generator = new Gen($faker);
        $now = new \DateTimeImmutable();

        $advisors = [
            new Advisor('alex.durand@example.com', 'Alex', 'Durand'),
            new Advisor('samia.benali@example.com', 'Samia', 'Benali'),
            new Advisor('julien.morel@example.com', 'Julien', 'Morel'),
        ];
        array_walk($advisors, $manager->persist(...));

        /** @var list<Client> $clients */
        $clients = [];

        foreach ($this->buckets() as $bucket) {
            for ($i = 0; $i < $bucket->count; ++$i) {
                // Environ 15 % des dossiers appartiennent à un client qui a déjà fait une demande
                $existing = [] !== $clients && $faker->boolean(15) ? $faker->randomElement($clients) : null;
                $dossier = $this->generateDossier($bucket, $generator, $faker, $now, $existing, $advisors);

                $client = $dossier->getClient();
                if (null === $existing) {
                    $client->backdateCreation($dossier->getSubmittedAt());
                    $client->setLastLoginAt($dossier->getSubmittedAt());
                    $clients[] = $client;
                    $manager->persist($client);
                }

                $manager->persist($dossier);
                foreach ($this->emailHistory($dossier, $now) as $email) {
                    $manager->persist($email);
                }
            }

            $manager->flush();
        }

        $manager->flush();
    }

    /**
     * @param list<Advisor> $advisors
     */
    private function generateDossier(Bucket $bucket, Gen $generator, \Faker\Generator $faker, \DateTimeImmutable $now, ?Client $existing, array $advisors): Dossier
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; ++$attempt) {
            $age = $faker->numberBetween($bucket->minAgeSeconds, $bucket->maxAgeSeconds);
            $submittedAt = $now->modify(\sprintf('-%d seconds', $age));
            $consent = $bucket->consent ?? $faker->boolean(55);

            $identity = null === $existing ? null : [
                'email' => $existing->getEmail(),
                'civility' => $existing->getCivility(),
                'firstName' => $existing->getFirstName(),
                'lastName' => $existing->getLastName(),
                'birthDate' => $existing->getBirthDate()->format('Y-m-d'),
                'phone' => $existing->getPhone(),
            ];

            $request = $generator->generate($faker->randomElement($bucket->profiles), $submittedAt, $consent, $identity);
            $result = $this->comparator->compare($request, $submittedAt->setTime(0, 0));
            $client = $existing ?? $this->dossierFactory->createClient($request, $submittedAt);

            $status = $bucket->pendingVerification ? DossierStatusEnum::PENDING_VERIFICATION : DossierStatusEnum::ACTIVE;
            $dossier = $this->dossierFactory->createDossier($client, $request, $result, $status)->backdate($submittedAt);

            $this->applyCallbackFollowUp($dossier, $bucket, $faker, $now, $advisors);
            $this->applyAction($dossier, $bucket, $faker, $now, $advisors);

            if (($bucket->accept)($dossier, $this->priorityResolver->assess($dossier, $now))) {
                return $dossier;
            }
        }

        throw new \RuntimeException(\sprintf('Fixtures : aucun dossier trouvé pour le cas « %s » après %d essais.', $bucket->name, self::MAX_ATTEMPTS));
    }

    /**
     * @param list<Advisor> $advisors
     */
    private function applyCallbackFollowUp(Dossier $dossier, Bucket $bucket, \Faker\Generator $faker, \DateTimeImmutable $now, array $advisors): void
    {
        if (null === $dossier->getConsentAdvisorCallbackAt() || !$faker->boolean(35)) {
            return; // TO_CALL, jamais traité
        }

        $statuses = [CallbackStatusEnum::NO_ANSWER, CallbackStatusEnum::CALLBACK_SCHEDULED];
        if ($bucket->allowClosedCallback) {
            array_push($statuses, CallbackStatusEnum::DECLINED, CallbackStatusEnum::CLOSED);
        }
        $status = $faker->randomElement($statuses);
        $notes = [
            CallbackStatusEnum::NO_ANSWER->value => 'Messagerie, message laissé.',
            CallbackStatusEnum::CALLBACK_SCHEDULED->value => 'Rappel convenu en fin de journée.',
            CallbackStatusEnum::DECLINED->value => 'Le client ne souhaite pas donner suite.',
            CallbackStatusEnum::CLOSED->value => 'Projet abandonné par le client.',
        ];

        $submitted = $dossier->getSubmittedAt()->getTimestamp();
        $at = (new \DateTimeImmutable())->setTimestamp($faker->numberBetween($submitted, max($submitted, $now->getTimestamp() - 60)));
        $dossier->recordCallbackFollowUp($faker->randomElement($advisors), $status, $notes[$status->value], $at);
    }

    /**
     * Souscription par le client, puis validation ou refus par un conseiller.
     *
     * @param list<Advisor> $advisors
     */
    private function applyAction(Dossier $dossier, Bucket $bucket, \Faker\Generator $faker, \DateTimeImmutable $now, array $advisors): void
    {
        if (Bucket::ACTION_NONE === $bucket->action || !$dossier->isActive() || [] === $dossier->getQuotes()) {
            return;
        }

        $submitted = $dossier->getSubmittedAt()->getTimestamp();
        $latest = min($now->getTimestamp() - 120, $dossier->getQuotesExpireAt()->getTimestamp() - 60);
        if ($latest <= $submitted + 60) {
            return;
        }

        $subscribedAt = (new \DateTimeImmutable())->setTimestamp($faker->numberBetween($submitted + 60, min($latest, $submitted + 2 * self::DAY)));
        $offer = $faker->boolean(70) ? $dossier->getQuotes()[0] : $faker->randomElement($dossier->getQuotes());
        $dossier->subscribe((string) $offer['offerCode'], $subscribedAt);

        if (Bucket::ACTION_SUBSCRIBE === $bucket->action) {
            return;
        }

        $decidedAt = (new \DateTimeImmutable())->setTimestamp($faker->numberBetween($subscribedAt->getTimestamp() + 60, max($subscribedAt->getTimestamp() + 60, $now->getTimestamp() - 60)));
        $approve = Bucket::ACTION_APPROVE === $bucket->action;
        $dossier->moveWorkflowTo($approve ? DossierWorkflowStatusEnum::APPROVED : DossierWorkflowStatusEnum::REJECTED, $decidedAt);

        if (null !== $dossier->getConsentAdvisorCallbackAt()) {
            $dossier->recordCallbackFollowUp(
                $faker->randomElement($advisors),
                CallbackStatusEnum::CLOSED,
                $approve ? 'Pièces reçues, dossier validé par le partenaire.' : 'Refus du partenaire après étude des justificatifs.',
                $decidedAt,
            );
        }
    }

    /**
     * Historique cohérent avec la chronologie du dossier : confirmation et transmission au résultat,
     * relance J+1 et relance J+7 (expiration) si les offres attendaient encore un choix à ces dates.
     *
     * @return list<EmailNotification>
     */
    private function emailHistory(Dossier $dossier, \DateTimeImmutable $now): array
    {
        if (!$dossier->isActive()) {
            return [];
        }

        $submittedAt = $dossier->getSubmittedAt();
        $types = [EmailNotificationTypeEnum::REQUEST_CONFIRMATION->value => $submittedAt->modify('+1 minute')];
        if (ScoringDecisionEnum::HARD_REJECT !== $dossier->getDecision()) {
            $types[EmailNotificationTypeEnum::PARTNER_TRANSMISSION->value] = $submittedAt->modify('+2 minutes');
        }

        if (ScoringDecisionEnum::INSTANT_OFFERS === $dossier->getDecision()) {
            $choiceAt = $dossier->getSubscribedAt() ?? $dossier->getQuotesExpireAt();
            $d1 = $submittedAt->modify('+1 day');
            $expiry = $submittedAt->modify('+5 days +1 hour');
            if ($d1 <= $now && $d1 < $choiceAt) {
                $types[EmailNotificationTypeEnum::REMINDER_D1->value] = $d1;
            }
            if ($expiry <= $now && $expiry < $choiceAt) {
                $types[EmailNotificationTypeEnum::EXPIRY_REMINDER->value] = $expiry;
            }
        }

        $dto = $this->mailFactory->build($dossier);
        $emails = [];
        foreach ($types as $type => $sentAt) {
            $handler = $this->mailHandlers[$type];
            $emails[] = new EmailNotification($dossier, $handler->getType(), $dto->recipientEmail, $handler->subjectFor($dto), EmailNotificationStatusEnum::SENT, $sentAt);
        }

        return $emails;
    }

    /**
     * 210 dossiers. Les critères d'acceptation portent sur le dossier réellement calculé.
     *
     * @return list<Bucket>
     */
    private function buckets(): array
    {
        $priority = static fn (CallbackPriorityEnum $expected): \Closure => static fn (Dossier $d, PriorityAssessment $a): bool => $a->priority === $expected;
        $workflow = static fn (DossierWorkflowStatusEnum $expected): \Closure => static fn (Dossier $d, PriorityAssessment $a): bool => $d->getWorkflowStatus() === $expected;
        $eligible = [Gen::STANDARD_ELIGIBLE, Gen::LARGE_ELIGIBLE];

        return [
            // RG-01 : dossiers chauds ajustables (< 72 h, endettement 33–40 %, blindage)
            new Bucket('P1 dossier chaud ajustable', 30, [Gen::GRAY_ZONE], self::HOUR, 70 * self::HOUR, $priority(CallbackPriorityEnum::P1)),
            // RG-02 : gros montants éligibles non souscrits (2 h – 36 h)
            new Bucket('P1 bis gros montant', 25, [Gen::LARGE_ELIGIBLE], 2 * self::HOUR + 60, 36 * self::HOUR - 60, $priority(CallbackPriorityEnum::P1_BIS)),
            // RG-03 : devis standard non souscrits (24 h – 48 h)
            new Bucket('P2 devis standard', 25, [Gen::STANDARD_ELIGIBLE], 24 * self::HOUR + 60, 48 * self::HOUR - 60, $priority(CallbackPriorityEnum::P2)),
            // RG-04 : dernière relance avant expiration (> 5 jours, < 7 jours)
            new Bucket('P3 avant expiration', 20, $eligible, 5 * self::DAY + self::HOUR, 7 * self::DAY - 2 * self::HOUR, $priority(CallbackPriorityEnum::P3)),
            // Offres choisies en cours de validation, validées, refusées après souscription
            new Bucket('Offre choisie (QUOTE_SUBSCRIBED)', 20, $eligible, 3 * self::HOUR, 6 * self::DAY, $workflow(DossierWorkflowStatusEnum::QUOTE_SUBSCRIBED), null, Bucket::ACTION_SUBSCRIBE),
            new Bucket('Dossier accepté (APPROVED)', 15, $eligible, 2 * self::DAY, 25 * self::DAY, $workflow(DossierWorkflowStatusEnum::APPROVED), null, Bucket::ACTION_APPROVE),
            new Bucket('Souscription refusée (REJECTED)', 5, $eligible, 2 * self::DAY, 20 * self::DAY, static fn (Dossier $d): bool => DossierWorkflowStatusEnum::REJECTED === $d->getWorkflowStatus() && null !== $d->getSubscribedAt(), null, Bucket::ACTION_REJECT_SUBSCRIPTION),
            // Refus fermes du moteur (endettement, reste à vivre, contrat, montant)
            new Bucket('Refus ferme (HARD_REJECT)', 25, [Gen::REJECT], self::HOUR, 25 * self::DAY, static fn (Dossier $d): bool => ScoringDecisionEnum::HARD_REJECT === $d->getDecision(), null),
            // File conseiller hors P1 (ancien, ou critères de blindage non remplis)
            new Bucket('À ajuster hors priorité', 20, [Gen::GRAY_ZONE], self::HOUR, 20 * self::DAY, static fn (Dossier $d, PriorityAssessment $a): bool => DossierWorkflowStatusEnum::NO_OFFER_ADVISOR_QUEUE === $d->getWorkflowStatus() && CallbackPriorityEnum::NONE === $a->priority, null, Bucket::ACTION_NONE, false, true),
            // Offres en attente sans priorité (pas de consentement, hors fenêtres, expirées, suivi clos)
            new Bucket('Offres sans priorité', 20, $eligible, 10 * 60, 15 * self::DAY, static fn (Dossier $d, PriorityAssessment $a): bool => DossierWorkflowStatusEnum::QUOTES_GENERATED === $d->getWorkflowStatus() && CallbackPriorityEnum::NONE === $a->priority, null, Bucket::ACTION_NONE, false, true),
            // Clients connus en attente de vérification par code (dossier invisible)
            new Bucket('En attente de vérification', 5, [...$eligible, Gen::GRAY_ZONE], 10 * 60, 2 * self::DAY, static fn (Dossier $d): bool => !$d->isActive(), null, Bucket::ACTION_NONE, true),
        ];
    }
}
