<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Client>
 */
final class ClientRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findOneByEmail(string $email): ?Client
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }
}
