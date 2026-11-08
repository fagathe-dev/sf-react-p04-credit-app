<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Advisor;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Advisor>
 */
final class AdvisorRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advisor::class);
    }

    public function findOneByEmail(string $email): ?Advisor
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }

    public function findActiveByEmail(string $email): ?Advisor
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email)), 'isActive' => true]);
    }
}
