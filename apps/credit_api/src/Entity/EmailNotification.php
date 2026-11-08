<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EmailNotificationStatusEnum;
use App\Enum\EmailNotificationTypeEnum;
use App\Repository\EmailNotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Historique des e-mails transactionnels d'un dossier : un envoi réussi au plus par type (DossierNotifier),
 * les échecs sont conservés et retentés.
 */
#[ORM\Entity(repositoryClass: EmailNotificationRepository::class)]
#[ORM\Table(name: 'email_notification')]
#[ORM\Index(name: 'idx_email_notification_dossier_type', columns: ['dossier_id', 'type'])]
class EmailNotification
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Dossier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Dossier $dossier;

    #[ORM\Column(length: 40, enumType: EmailNotificationTypeEnum::class)]
    private EmailNotificationTypeEnum $type;

    #[ORM\Column(length: 180)]
    private string $recipient;

    #[ORM\Column(length: 255)]
    private string $subject;

    #[ORM\Column(length: 20, enumType: EmailNotificationStatusEnum::class)]
    private EmailNotificationStatusEnum $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $sentAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $error = null;

    public function __construct(Dossier $dossier, EmailNotificationTypeEnum $type, string $recipient, string $subject, EmailNotificationStatusEnum $status, ?\DateTimeImmutable $sentAt = null, ?string $error = null)
    {
        $this->id = Uuid::v7();
        $this->dossier = $dossier;
        $this->type = $type;
        $this->recipient = $recipient;
        $this->subject = mb_substr($subject, 0, 255);
        $this->status = $status;
        $this->sentAt = $sentAt ?? new \DateTimeImmutable();
        $this->error = null === $error ? null : mb_substr($error, 0, 255);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDossier(): Dossier
    {
        return $this->dossier;
    }

    public function getType(): EmailNotificationTypeEnum
    {
        return $this->type;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getStatus(): EmailNotificationStatusEnum
    {
        return $this->status;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
