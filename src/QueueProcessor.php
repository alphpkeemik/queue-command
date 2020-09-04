<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use Psr\Log\LoggerInterface;

/**
 * @author mati.andreas@ambientia.ee
 */
class QueueProcessor
{
    /**
     * @var QueueRepository
     */
    private $repository;

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

    public function __construct(QueueRepository $repository, LoggerInterface $logger, EntityProcessor $entityProcessor, LockProvider $lockProvider, TimeProvider $timeProvider)
    {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->entityProcessor = $entityProcessor;
        $this->lockProvider = $lockProvider;
        $this->timeProvider = $timeProvider;
    }


    public function process(QueueCriteria $criteria, int $timeLimit = null): void
    {
        $count = 0;
        $time = $this->time();


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
        $offset = 0;
        while ($isInTimeLimit() and ($command = $this->repository->getNextToExecute($criteria, $offset))) {
            $lock = $this->lockProvider->create($command);
            if (!$lock->acquire(false)) {
                $this->logger->debug('Unable acquire lock', [
                    'command_id' => $command->getId(),
                ]);
                $offset++;
                continue;
            }
            $this->entityProcessor->process($command, $this->repository);
            $offset = 0;
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