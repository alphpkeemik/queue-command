<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @author mati.andreas@ambientia.ee
 */
class CrashedProcessor
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
     * @var LockProvider
     */
    private $lockProvider;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        LockProvider $lockProvider,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->lockProvider = $lockProvider;
        $this->eventDispatcher = $eventDispatcher;
    }


    public function process(): void
    {
        $em = $this->doctrine->getManagerForClass(QueueCommandEntity::class);
        $repo = $em->getRepository(QueueCommandEntity::class);

        $criteria = ['status' => States::PROCESSING];
        $order = ['id' => 'ASC'];
        $offset = 0;
        while (($command = current($repo->findBy($criteria, $order, 1, $offset)))) {
            $offset++;
            $lock = $this->lockProvider->create($command);
            if (!$lock->acquire(false)) {
                continue;
            }
            $this->logger->error('Found crashed worker', [
                'command_id' => $command->getId(),
            ]);
            $lock->release();
            $command->setStatus(States::FATAL);
            $this->eventDispatcher->dispatch(new Event($command, Events::EXECUTE_FATAL));
            $em->flush();
        }
        $em->clear();
    }
}