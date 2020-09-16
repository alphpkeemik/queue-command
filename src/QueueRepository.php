<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;

/**
 * @author mati.andreas@ambientia.ee
 */
class QueueRepository
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getNextToExecute(QueueCriteria $criteria, int $offset): ?QueueCommandEntity
    {
        $em = $this->doctrine->getManagerForClass(QueueCommandEntity::class);
        $repo = $em->getRepository(QueueCommandEntity::class);
        if (!$repo instanceof Selectable) {
            throw new LogicException(sprintf(
                'Only %s repository supported', Selectable::class
            ));
        }
        $innerCriteria = new Criteria(
            $criteria->getWhereExpression(),
            $criteria->getOrderings(),
            $offset, 1
        );

        $data = $repo->matching($innerCriteria);
        return $data->count() ? $data->current() : null;
    }

    public function flush(QueueCommandEntity $entity): void
    {
        $objectManager = $this->doctrine->getManagerForClass(QueueCommandEntity::class);
        if (!$objectManager->contains($entity)) {
            //todo replace it with find
            $objectManager->merge($entity);
        }
        $objectManager->flush();
    }

}
