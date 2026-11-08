<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Advisor;
use App\Entity\Client;
use App\Entity\OtpCode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<OtpCode>
 */
final class OtpCodeRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OtpCode::class);
    }

    /** Dernier code encore utilisable (non consommé, non expiré) du titulaire. */
    public function findLatestUsable(Client|Advisor $owner, \DateTimeImmutable $now): ?OtpCode
    {
        /** @var OtpCode|null */
        return $this->ownerQueryBuilder($owner)
            ->andWhere('o.consumedAt IS NULL')
            ->andWhere('o.expiresAt > :now')
            ->setParameter('now', $now)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Dernier code émis (délai entre deux envois). */
    public function findLatest(Client|Advisor $owner): ?OtpCode
    {
        /** @var OtpCode|null */
        return $this->ownerQueryBuilder($owner)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Codes encore utilisables du titulaire (à invalider avant d'en émettre un nouveau).
     *
     * @return list<OtpCode>
     */
    public function findUsable(Client|Advisor $owner, \DateTimeImmutable $now): array
    {
        /** @var list<OtpCode> */
        return $this->ownerQueryBuilder($owner)
            ->andWhere('o.consumedAt IS NULL')
            ->andWhere('o.expiresAt > :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    private function ownerQueryBuilder(Client|Advisor $owner): QueryBuilder
    {
        $field = $owner instanceof Client ? 'o.client' : 'o.advisor';

        return $this->createQueryBuilder('o')
            ->andWhere($field . ' = :owner')
            ->setParameter('owner', $owner->getId(), 'uuid');
    }
}
