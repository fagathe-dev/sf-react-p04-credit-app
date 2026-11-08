<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\EmailNotification;
use App\Enum\EmailNotificationStatusEnum;
use App\Enum\EmailNotificationTypeEnum;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<EmailNotification>
 */
final class EmailNotificationRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailNotification::class);
    }

    public function existsSentFor(Dossier $dossier, EmailNotificationTypeEnum $type): bool
    {
        return null !== $this->findOneBy(['dossier' => $dossier, 'type' => $type, 'status' => EmailNotificationStatusEnum::SENT]);
    }

    /**
     * @return list<EmailNotification>
     */
    public function findByDossier(Dossier $dossier): array
    {
        /** @var list<EmailNotification> */
        return $this->findBy(['dossier' => $dossier], ['sentAt' => 'ASC']);
    }
}
