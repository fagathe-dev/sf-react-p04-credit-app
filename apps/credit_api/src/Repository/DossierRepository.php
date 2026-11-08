<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Dossier;
use App\Enum\DossierWorkflowStatusEnum;
use Credit\Core\Enum\CallbackStatusEnum;
use Credit\Core\Enum\DossierStatusEnum;
use Credit\Core\Enum\ScoringDecisionEnum;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends AbstractRepository<Dossier>
 */
final class DossierRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    /**
     * Dossiers ACTIVE d'un client, du plus récent au plus ancien.
     *
     * @return list<Dossier>
     */
    public function findActiveByClient(Client $client): array
    {
        /** @var list<Dossier> */
        return $this->createQueryBuilder('d')
            ->andWhere('d.client = :client')
            ->andWhere('d.status = :active')
            ->setParameter('client', $client->getId(), 'uuid')
            ->setParameter('active', DossierStatusEnum::ACTIVE)
            ->orderBy('d.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveForClient(Uuid $id, Client $client): ?Dossier
    {
        $dossier = $this->find($id);

        return null !== $dossier && $dossier->isActive() && $dossier->getClient()->getId()->equals($client->getId()) ? $dossier : null;
    }

    public function findPendingForClient(Uuid $id, Client $client): ?Dossier
    {
        $dossier = $this->find($id);

        return null !== $dossier
            && DossierStatusEnum::PENDING_VERIFICATION === $dossier->getStatus()
            && $dossier->getClient()->getId()->equals($client->getId()) ? $dossier : null;
    }

    /**
     * Dossier accessible au back-office : ACTIVE et avec consentement de rappel.
     */
    public function findCallbackDossier(Uuid $id): ?Dossier
    {
        /** @var Dossier|null */
        return $this->callbackQueryBuilder()
            ->andWhere('d.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Dossiers de la file conseiller (ACTIVE avec consentement de rappel), filtrés en SQL.
     * La priorité dépend de l'heure : elle est calculée ensuite par CallbackPriorityResolver.
     *
     * @param \DateTimeImmutable|null $submittedSince limite aux résultats récents (toutes les règles RG portent sur 7 jours au plus)
     *
     * @return list<Dossier>
     */
    public function findCallbackCandidates(?ScoringDecisionEnum $decision, ?CallbackStatusEnum $callbackStatus, ?DossierWorkflowStatusEnum $workflowStatus, ?\DateTimeImmutable $submittedSince): array
    {
        $qb = $this->callbackQueryBuilder()
            ->addSelect('c')
            ->orderBy('d.submittedAt', 'ASC');

        if (null !== $decision) {
            $qb->andWhere('d.decision = :decision')->setParameter('decision', $decision);
        }

        if (null !== $callbackStatus) {
            $qb->andWhere('d.callbackStatus = :callbackStatus')->setParameter('callbackStatus', $callbackStatus);
        }

        if (null !== $workflowStatus) {
            $qb->andWhere('d.workflowStatus = :workflowStatus')->setParameter('workflowStatus', $workflowStatus);
        }

        if (null !== $submittedSince) {
            $qb->andWhere('d.submittedAt >= :since')->setParameter('since', $submittedSince);
        }

        /** @var list<Dossier> */
        return $qb->getQuery()->getResult();
    }

    /**
     * Dossiers actifs dont les offres attendent un choix, résultat compris entre $since et $until (relances e-mail).
     *
     * @return list<Dossier>
     */
    public function findAwaitingOfferChoice(\DateTimeImmutable $since, \DateTimeImmutable $until): array
    {
        /** @var list<Dossier> */
        return $this->createQueryBuilder('d')
            ->innerJoin('d.client', 'c')
            ->addSelect('c')
            ->andWhere('d.status = :active')
            ->andWhere('d.workflowStatus = :awaiting')
            ->andWhere('d.submittedAt >= :since')
            ->andWhere('d.submittedAt <= :until')
            ->setParameter('active', DossierStatusEnum::ACTIVE)
            ->setParameter('awaiting', DossierWorkflowStatusEnum::QUOTES_GENERATED)
            ->setParameter('since', $since)
            ->setParameter('until', $until)
            ->getQuery()
            ->getResult();
    }

    private function callbackQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.client', 'c')
            ->andWhere('d.status = :active')
            ->andWhere('d.consentAdvisorCallbackAt IS NOT NULL')
            ->setParameter('active', DossierStatusEnum::ACTIVE);
    }
}
