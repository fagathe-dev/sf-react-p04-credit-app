<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OtpCodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Code de connexion à usage unique, stocké haché. Lié à un client ou à un conseiller,
 * et éventuellement à un dossier en attente de vérification.
 */
#[ORM\Entity(repositoryClass: OtpCodeRepository::class)]
#[ORM\Table(name: 'otp_code')]
#[ORM\Index(name: 'idx_otp_created_at', columns: ['created_at'])]
class OtpCode
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: Advisor::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Advisor $advisor = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Dossier $dossier = null;

    #[ORM\Column(length: 64)]
    private string $codeHash;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column]
    private int $attempts = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $codeHash, \DateTimeImmutable $expiresAt)
    {
        $this->id = Uuid::v7();
        $this->codeHash = $codeHash;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function forClient(Client $client, string $codeHash, \DateTimeImmutable $expiresAt, ?Dossier $dossier = null): self
    {
        $otp = new self($codeHash, $expiresAt);
        $otp->client = $client;
        $otp->dossier = $dossier;

        return $otp;
    }

    public static function forAdvisor(Advisor $advisor, string $codeHash, \DateTimeImmutable $expiresAt): self
    {
        $otp = new self($codeHash, $expiresAt);
        $otp->advisor = $advisor;

        return $otp;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function getAdvisor(): ?Advisor
    {
        return $this->advisor;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function incrementAttempts(): static
    {
        ++$this->attempts;

        return $this;
    }

    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function consume(): static
    {
        $this->consumedAt = new \DateTimeImmutable();

        return $this;
    }

    /** Invalide le code sans le consommer (nouveau code émis, trop d'essais). */
    public function invalidate(): static
    {
        $this->expiresAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isUsable(\DateTimeImmutable $now): bool
    {
        return null === $this->consumedAt && $this->expiresAt > $now;
    }
}
