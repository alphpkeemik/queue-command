<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * @author mati.andreas@ambientia.ee
 */
class QueueProcessor
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityProcessor
     */
    private $entityProcessor;

    /**
     * @var LockProvider
     */
    private $lockProvider;

    /**
     * @var TimeProvider
     */
    private $timeProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        EntityProcessor $entityProcessor,
        LockProvider $lockProvider,
        TimeProvider $timeProvider
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->entityProcessor = $entityProcessor;
        $this->lockProvider = $lockProvider;
        $this->timeProvider = $timeProvider;
    }


    public function process(QueueCriteria $criteria, int $timeLimit = null): void
    {
        $count = 0;
        $time = $this->time();
        $em = $this->doctrine->getManagerForClass(QueueCommandEntity::class);
        $repo = $em->getRepository(QueueCommandEntity::class);
        if (!$repo instanceof Selectable) {
            throw new LogicException(sprintf(
                'Only %s repository supported', Selectable::class
            ));
        }

        $isInTimeLimit = function () use (& $count, & $timeLimit, & $time) : bool {
            if (!$count) {
                return true;
            }
            if (!$timeLimit) {
                return true;
            }
            $timeSpent = $this->time() - $time;
            if ($timeSpent >= $timeLimit) {
                $this->logger->debug('Breaking after time limit', [
                    'timeLimit' => $timeLimit,
                    'elapsed' => $timeSpent,
                ]);

                return false;
            }

            return true;
        };

        $innerCriteria = new Criteria($criteria->getWhereExpression(), $criteria->getOrderings(), 0, 1);
        while ($isInTimeLimit() and ($command = $repo->matching($innerCriteria)->current())) {
            $lock = $this->lockProvider->create($command);
            if (!$lock->acquire(false)) {
                $this->logger->debug('Unable acquire lock', [
                    'command_id' => $command->getId(),
                ]);
                $innerCriteria->setFirstResult(
                    $innerCriteria->getFirstResult() + 1
                );
                continue;
            }
            $this->entityProcessor->process($command, $em);
            $innerCriteria->setFirstResult(0);
            $count++;

            $lock->release();

        }

        $this->logger->debug('Processed command count', [
            'count' => $count
        ]);
    }

    private function time(): int
    {
        return $this->timeProvider->time();
    }


}