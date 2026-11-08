<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\DossierWorkflowStatusEnum;
use App\Repository\DossierRepository;
use Credit\Core\Enum\CallbackStatusEnum;
use Credit\Core\Enum\DossierStatusEnum;
use Credit\Core\Enum\ScoringDecisionEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Un passage dans le tunnel : demande, résultat figé (devis, pistes ou motifs) et suivi d'appel.
 *
 * Les colonnes JSON contiennent les DTO de credit-core normalisés (centimes et points de base).
 */
#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\Table(name: 'dossier')]
#[ORM\Index(name: 'idx_dossier_client_status', columns: ['client_id', 'status'])]
#[ORM\Index(name: 'idx_dossier_callback', columns: ['status', 'consent_advisor_callback_at', 'decision'])]
#[ORM\Index(name: 'idx_dossier_workflow', columns: ['status', 'workflow_status', 'submitted_at'])]
class Dossier
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Client $client;

    #[ORM\Column(length: 30, enumType: DossierStatusEnum::class)]
    private DossierStatusEnum $status;

    /** Date du résultat. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $submittedAt;

    #[ORM\Column(length: 30, enumType: ScoringDecisionEnum::class)]
    private ScoringDecisionEnum $decision;

    /** @var array<string, mixed> Les 8 étapes, sans les consentements. */
    #[ORM\Column(type: Types::JSON)]
    private array $requestSnapshot = [];

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $scoringSummary = [];

    /** @var list<array<string, mixed>> */
    #[ORM\Column(type: Types::JSON)]
    private array $quotes = [];

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $advisorFallback = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rejection = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $consentPartnerTransmissionAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $consentAdvisorCallbackAt = null;

    /** @var array<string, string|null>|null utm_source, utm_medium, utm_campaign, referrer */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $acquisition = null;

    #[ORM\Column(length: 30, nullable: true, enumType: CallbackStatusEnum::class)]
    private ?CallbackStatusEnum $callbackStatus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $advisorNote = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $callbackUpdatedAt = null;

    #[ORM\ManyToOne(targetEntity: Advisor::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Advisor $callbackAdvisor = null;

    #[ORM\Column(length: 30, enumType: DossierWorkflowStatusEnum::class)]
    private DossierWorkflowStatusEnum $workflowStatus;

    /** Fin de validité des offres (résultat + QUOTE_VALIDITY_DAYS). */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $quotesExpireAt;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $subscribedOfferCode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $subscribedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $workflowUpdatedAt = null;

    public const int QUOTE_VALIDITY_DAYS = 7;

    public function __construct(Client $client, DossierStatusEnum $status, ScoringDecisionEnum $decision)
    {
        $this->id = Uuid::v7();
        $this->client = $client;
        $this->status = $status;
        $this->decision = $decision;
        $this->workflowStatus = DossierWorkflowStatusEnum::fromDecision($decision);
        $this->submittedAt = new \DateTimeImmutable();
        $this->consentPartnerTransmissionAt = $this->submittedAt;
        $this->quotesExpireAt = self::expiryFrom($this->submittedAt);
    }

    private static function expiryFrom(\DateTimeImmutable $submittedAt): \DateTimeImmutable
    {
        return $submittedAt->modify(\sprintf('+%d days', self::QUOTE_VALIDITY_DAYS));
    }

    /**
     * Antidate le dossier (jeux de données de test uniquement) : date du résultat, consentements et expiration.
     */
    public function backdate(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;
        $this->consentPartnerTransmissionAt = $submittedAt;
        $this->quotesExpireAt = self::expiryFrom($submittedAt);
        if (null !== $this->consentAdvisorCallbackAt) {
            $this->consentAdvisorCallbackAt = $submittedAt;
        }

        return $this;
    }

    public function getWorkflowStatus(): DossierWorkflowStatusEnum
    {
        return $this->workflowStatus;
    }

    public function getQuotesExpireAt(): \DateTimeImmutable
    {
        return $this->quotesExpireAt;
    }

    public function areQuotesExpired(\DateTimeImmutable $now): bool
    {
        return $now >= $this->quotesExpireAt;
    }

    public function getSubscribedOfferCode(): ?string
    {
        return $this->subscribedOfferCode;
    }

    public function getSubscribedAt(): ?\DateTimeImmutable
    {
        return $this->subscribedAt;
    }

    public function getWorkflowUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->workflowUpdatedAt;
    }

    public function hasQuote(string $offerCode): bool
    {
        foreach ($this->quotes as $quote) {
            if (($quote['offerCode'] ?? null) === $offerCode) {
                return true;
            }
        }

        return false;
    }

    /** Le client peut choisir une offre : offres générées, non expirées, offre présente dans le dossier. */
    public function canSubscribe(string $offerCode, \DateTimeImmutable $now): bool
    {
        return $this->isActive()
            && DossierWorkflowStatusEnum::QUOTES_GENERATED === $this->workflowStatus
            && !$this->areQuotesExpired($now)
            && $this->hasQuote($offerCode);
    }

    public function subscribe(string $offerCode, \DateTimeImmutable $now): static
    {
        if (!$this->canSubscribe($offerCode, $now)) {
            throw new \LogicException('Cette offre ne peut pas être choisie.');
        }

        $this->subscribedOfferCode = $offerCode;
        $this->subscribedAt = $now;
        $this->workflowStatus = DossierWorkflowStatusEnum::QUOTE_SUBSCRIBED;
        $this->workflowUpdatedAt = $now;

        return $this;
    }

    public function canAdvisorMoveTo(DossierWorkflowStatusEnum $target): bool
    {
        return \in_array($target, $this->workflowStatus->advisorTransitions(), true);
    }

    /** Validation ou refus d'une souscription par un conseiller. */
    public function moveWorkflowTo(DossierWorkflowStatusEnum $target, \DateTimeImmutable $now): static
    {
        if (!$this->canAdvisorMoveTo($target)) {
            throw new \LogicException(\sprintf('Transition %s → %s non autorisée.', $this->workflowStatus->value, $target->value));
        }

        $this->workflowStatus = $target;
        $this->workflowUpdatedAt = $now;

        return $this;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getStatus(): DossierStatusEnum
    {
        return $this->status;
    }

    public function setStatus(DossierStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return DossierStatusEnum::ACTIVE === $this->status;
    }

    public function getSubmittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getDecision(): ScoringDecisionEnum
    {
        return $this->decision;
    }

    /** @return array<string, mixed> */
    public function getRequestSnapshot(): array
    {
        return $this->requestSnapshot;
    }

    /** @param array<string, mixed> $requestSnapshot */
    public function setRequestSnapshot(array $requestSnapshot): static
    {
        $this->requestSnapshot = $requestSnapshot;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getScoringSummary(): array
    {
        return $this->scoringSummary;
    }

    /** @param array<string, mixed> $scoringSummary */
    public function setScoringSummary(array $scoringSummary): static
    {
        $this->scoringSummary = $scoringSummary;

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function getQuotes(): array
    {
        return $this->quotes;
    }

    /** @param list<array<string, mixed>> $quotes */
    public function setQuotes(array $quotes): static
    {
        $this->quotes = $quotes;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getAdvisorFallback(): ?array
    {
        return $this->advisorFallback;
    }

    /** @param array<string, mixed>|null $advisorFallback */
    public function setAdvisorFallback(?array $advisorFallback): static
    {
        $this->advisorFallback = $advisorFallback;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getRejection(): ?array
    {
        return $this->rejection;
    }

    /** @param array<string, mixed>|null $rejection */
    public function setRejection(?array $rejection): static
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function getConsentPartnerTransmissionAt(): \DateTimeImmutable
    {
        return $this->consentPartnerTransmissionAt;
    }

    public function getConsentAdvisorCallbackAt(): ?\DateTimeImmutable
    {
        return $this->consentAdvisorCallbackAt;
    }

    /**
     * Donne (date du jour) ou retire (null) le consentement de rappel.
     * Le premier consentement place le dossier dans la file de rappels (TO_CALL).
     */
    public function setConsentAdvisorCallback(bool $consent): static
    {
        if ($consent) {
            $this->consentAdvisorCallbackAt ??= new \DateTimeImmutable();
            $this->callbackStatus ??= CallbackStatusEnum::TO_CALL;
        } else {
            $this->consentAdvisorCallbackAt = null;
        }

        return $this;
    }

    /** @return array<string, string|null>|null */
    public function getAcquisition(): ?array
    {
        return $this->acquisition;
    }

    /** @param array<string, string|null>|null $acquisition */
    public function setAcquisition(?array $acquisition): static
    {
        $this->acquisition = $acquisition;

        return $this;
    }

    public function getCallbackStatus(): ?CallbackStatusEnum
    {
        return $this->callbackStatus;
    }

    public function getAdvisorNote(): ?string
    {
        return $this->advisorNote;
    }

    public function getCallbackUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->callbackUpdatedAt;
    }

    public function getCallbackAdvisor(): ?Advisor
    {
        return $this->callbackAdvisor;
    }

    public function recordCallbackFollowUp(Advisor $advisor, CallbackStatusEnum $status, ?string $note, ?\DateTimeImmutable $at = null): static
    {
        $this->callbackStatus = $status;
        $this->advisorNote = $note;
        $this->callbackAdvisor = $advisor;
        $this->callbackUpdatedAt = $at ?? new \DateTimeImmutable();

        return $this;
    }
}
