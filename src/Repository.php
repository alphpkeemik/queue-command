<?php

namespace Ambientia\QueueCommand;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author mati.andreas@ambientia.ee
 */
class Repository
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    private $flushNeeded = false;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function countQueuedByService(string $service): int
    {
        $qb = $this->createQueryBuilder();

        $expr = $qb->expr();
        $qb->select($expr->count('c.id'));
        $qb->andWhere($expr->eq('c.service', ':service'));
        $qb->andWhere($qb->expr()->isNull('c.status'));
        $qb->setParameter('service', $service);

        $qb->andWhere($expr->isNull('c.ttl'));

        return $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    public function insertIfNotExists(
        string $service,
        DateTime $ttl = null,
        int $priority = null,
        ...$arguments
    ): bool {
        if ($this->isQueued($service, $arguments, $ttl)) {
            return false;
        }
        $queueCommand = new QueueCommandEntity();
        $queueCommand->setService($service);
        $queueCommand->setArguments($arguments);
        if ($ttl) {
            $queueCommand->setTtl($ttl);
        }
        if (null !== $priority) {
            $queueCommand->setPriority($priority);
        }
        $this->getEm()->persist($queueCommand);
        $this->flushNeeded = true;

        return true;
    }

    public function flushAndClear(): void
    {
        if ($this->flushNeeded) {
            $this->flushNeeded = false;
            $em = $this->getEm();
            $em->flush();
            $em->clear();
        }
    }

    // privates below
    private function getEm()
    {
        return $this->doctrine->getManagerForClass(QueueCommandEntity::class);
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $em = $this->getEm();
        $repo = $em->getRepository(QueueCommandEntity::class);

        /** @var QueryBuilder $qb */
        $qb = $repo->createQueryBuilder('c');

        return $qb;
    }

    private function isQueued(string $service, array $arguments, DateTime $ttl = null): ?int
    {
        $qb = $this->createQueryBuilder();

        $expr = $qb->expr();
        $qb->select('c.id');
        $qb->andWhere($expr->eq('c.service', ':service'));
        $qb->andWhere($qb->expr()->isNull('c.status'));
        $qb->andWhere($expr->eq('c.arguments', ':arguments'));
        $qb->setParameter('service', $service);
        $qb->setParameter('arguments', $arguments, Types::OBJECT);
        $qb->setMaxResults(1);

        if ($ttl) {
            $qb->andWhere($expr->eq('c.ttl', ':ttl'));
            $qb->setParameter('ttl', $ttl);
        } else {
            $qb->andWhere($expr->isNull('c.ttl'));
        }

        return $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
    }
}
