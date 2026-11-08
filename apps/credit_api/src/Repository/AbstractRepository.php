<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Point d'écriture unique : les services n'injectent jamais EntityManagerInterface.
 *
 * @template T of object
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractRepository extends ServiceEntityRepository
{
    /**
     * @param T $entity
     */
    public function save(object $entity, bool $flush = true): bool
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return true;
    }

    /**
     * @param T $entity
     */
    public function remove(object $entity, bool $flush = true): bool
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return true;
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
