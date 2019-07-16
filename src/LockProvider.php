<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand;

use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\StoreInterface;

/**
 * @author mati.andreas@ambientia.ee
 */
class LockProvider
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreInterface
     */
    private $store;

    public function __construct(LoggerInterface $logger, StoreInterface $store)
    {
        $this->logger = $logger;
        $this->store = $store;
    }

    public function create(QueueCommandEntity $commandEntity): LockInterface
    {
        $lock = new Lock(new Key((string)$commandEntity->getId()), $this->store, null, false);
        $lock->setLogger($this->logger);

        return $lock;
    }
}