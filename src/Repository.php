<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
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

    /**
     * @var HashGenerator
     */
    private $hashGenerator;

    public function __construct(ManagerRegistry $doctrine, HashGenerator $hashGenerator)
    {
        $this->doctrine = $doctrine;
        $this->hashGenerator = $hashGenerator;
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

    /**
     * @param string $service
     * @param array  $arguments
     *
     * @return array|QueueCommandEntity[]
     */
    public function getQueuedByServiceAndArguments(string $service, array $arguments): array
    {
        $qb = $this->createQueryBuilder();

        $expr = $qb->expr();

        $hash = $this->hashGenerator->generate($service, $arguments);

        $qb->andWhere($expr->eq('c.hash', ':hash'));
        $qb->andWhere($qb->expr()->isNull('c.status'));
        $qb->setParameter('hash', $hash);

        return $qb->getQuery()->getResult();
    }


    public function insert(
        string $service,
        DateTime $ttl = null,
        int $priority = null,
        ...$arguments
    ): void {
        $hash = $this->hashGenerator->generate($service, $arguments);
        $queueCommand = new QueueCommandEntity(
            $service,
            $arguments,
            $hash,
            $ttl
        );

        if (null !== $priority) {
            $queueCommand->setPriority($priority);
        }
        /** @var EntityManagerInterface $objectManager */
        $objectManager = $this->doctrine->getManagerForClass(QueueCommandEntity::class);
        /** @var ClassMetadataInfo $metaData */
        $metaData = $objectManager->getMetadataFactory()->getMetadataFor(QueueCommandEntity::class);

        $objectManager->getConnection()->insert(
            $metaData->table['name'],
            [
                $metaData->getColumnName('service') => $service,
                $metaData->getColumnName('arguments') => $arguments,
                $metaData->getColumnName('hash') => $hash,
                $metaData->getColumnName('ttl') => $ttl,
                $metaData->getColumnName('priority') => $priority ?: 0,
            ],
            [
                $metaData->fieldMappings['service']['type'],
                $metaData->fieldMappings['arguments']['type'],
                $metaData->fieldMappings['hash']['type'],
                $metaData->fieldMappings['ttl']['type'],
                $metaData->fieldMappings['priority']['type'],
            ]
        );


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
        $this->insert($service, $ttl, $priority, ...$arguments);

        return true;
    }

    public function getNextToExecute(QueueCriteria $criteria, int $offset):?QueueCommandEntity
    {
        $em = $this->doctrine->getManagerForClass(QueueCommandEntity::class);
        $repo = $em->getRepository(QueueCommandEntity::class);
        if (!$repo instanceof Selectable) {
            throw new LogicException(sprintf(
                'Only %s repository supported', Selectable::class
            ));
        }
        $innerCriteria = new Criteria($criteria->getWhereExpression(), $criteria->getOrderings(), 0, 1);

        $data = $repo->matching($innerCriteria);
        return $data->count() ? $data->current() : null;
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

        $hash = $this->hashGenerator->generate($service, $arguments);

        $qb->andWhere($expr->eq('c.hash', ':hash'));
        $qb->andWhere($qb->expr()->isNull('c.status'));
        $qb->setParameter('hash', $hash);
        $qb->setMaxResults(1);

        if ($ttl) {
            $qb->andWhere($expr->eq('c.ttl', ':ttl'));
            $qb->setParameter('ttl', $ttl);
        } else {
            $qb->andWhere($expr->isNull('c.ttl'));
        }
        $query = $qb->getQuery();
        $result = $query->execute();

        return $result ? true : false;
    }
}
