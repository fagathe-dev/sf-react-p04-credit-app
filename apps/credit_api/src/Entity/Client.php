<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClientRepository;
use App\Security\User\SessionUserInterface;
use Credit\Core\Enum\CivilityEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Applicant ayant terminé le tunnel : espace client identifié par son e-mail, sans mot de passe.
 */
#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'client')]
#[ORM\UniqueConstraint(name: 'uniq_client_email', columns: ['email'])]
class Client implements SessionUserInterface
{
    public const string ROLE = 'ROLE_CLIENT';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 10, enumType: CivilityEnum::class)]
    private CivilityEnum $civility;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $birthDate;

    #[ORM\Column(length: 20)]
    private string $phone;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $consentClientSpaceCreatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    public function __construct(string $email)
    {
        $this->id = Uuid::v7();
        $this->email = mb_strtolower(trim($email));
        $this->createdAt = new \DateTimeImmutable();
        $this->consentClientSpaceCreatedAt = $this->createdAt;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCivility(): CivilityEnum
    {
        return $this->civility;
    }

    public function setCivility(CivilityEnum $civility): static
    {
        $this->civility = $civility;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getBirthDate(): \DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getConsentClientSpaceCreatedAt(): \DateTimeImmutable
    {
        return $this->consentClientSpaceCreatedAt;
    }

    public function setConsentClientSpaceCreatedAt(\DateTimeImmutable $at): static
    {
        $this->consentClientSpaceCreatedAt = $at;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** Antidate la création de l'espace (jeux de données de test uniquement). */
    public function backdateCreation(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        $this->consentClientSpaceCreatedAt = $createdAt;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getRoles(): array
    {
        return [self::ROLE];
    }

    public function getUserIdentifier(): string
    {
        return $this->id->toRfc4122();
    }

    public function isSessionAllowed(): bool
    {
        return true;
    }
}
