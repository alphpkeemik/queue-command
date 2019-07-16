<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\LockProvider;
use Ambientia\QueueCommand\QueueCommandEntity;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\StoreInterface;

/**
 * @author mati.andreas@ambientia.ee
 */
class LockProviderTest extends TestCase
{

    public function testAcceptance(): void
    {

        $logger = $this->createMock(LoggerInterface::class);
        $store = $this->createMock(StoreInterface::class);
        $entity = $this->createMock(QueueCommandEntity::class);
        $id = rand();
        $entity
            ->expects($this->once())
            ->method('getId')
            ->willReturn($id);

        $store
            ->expects($this->any())
            ->method('save')
            ->willReturnCallback(function (Key $key) use ($id) {
                return (int)(string)$key === $id;
            });
        $logger
            ->expects($this->any())
            ->method($this->callback(function (string $name) {
                return true;
            }));

        $provider = new LockProvider($logger, $store);
        $lock = $provider->create($entity);
        $lock->acquire(false);

        $lock->release();

        $this->assertTrue(true);
    }


}