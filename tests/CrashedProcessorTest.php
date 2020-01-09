<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\CrashedProcessor;
use Ambientia\QueueCommand\Event;
use Ambientia\QueueCommand\Events;
use Ambientia\QueueCommand\LockProvider;
use Ambientia\QueueCommand\QueueCommandEntity;
use Ambientia\QueueCommand\States;
use ArrayObject;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockInterface;

/**
 * @author mati.andreas@ambientia.ee
 */
class CrashedProcessorTest extends TestCase
{
    use StackMockTrait;

    private function createRepository(QueueCommandEntity $entity = null)
    {
        if (!$entity) {
            $entity = $this->createMock(QueueCommandEntity::class);
        }
        $data = [
            $entity
        ];
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects($this->any())
            ->method('findBy')
            ->willReturnCallback(function () use (&$data) {
                return [array_shift($data)];
            });

        return $repository;
    }

    private function createDoctrine(QueueCommandEntity $entity = null)
    {
        $repository = $this->createRepository($entity);
        $manager = $this->createConfiguredMock(ObjectManager::class, [
            'getRepository' => $repository

        ]);

        $doctrine = $this->createConfiguredMock(ManagerRegistry::class, [
            'getManagerForClass' => $manager
        ]);

        return $doctrine;
    }

    private function createLockProvider()
    {
        $lock = $this->createConfiguredMock(LockInterface::class, [
            'acquire' => true
        ]);
        $lockProvider = $this->createConfiguredMock(LockProvider::class, [
            'create' => $lock
        ]);

        return $lockProvider;
    }

    private function executeProcessor(
        ManagerRegistry $doctrine = null,
        LoggerInterface $logger = null,
        LockProvider $lockProvider = null,
        EventDispatcherInterface $eventDispatcher = null
    ): void {
        if (!$doctrine) {
            $doctrine = $this->createDoctrine();
        }
        if (!$logger) {
            $logger = $this->createMock(LoggerInterface::class);
        }
        if (!$lockProvider) {
            $lockProvider = $this->createLockProvider();
        }

        if (!$eventDispatcher) {
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        }
        $processor = new CrashedProcessor($doctrine, $logger, $lockProvider, $eventDispatcher);
        $processor->process();
    }

    public function testAcceptance(): void
    {
        $entity = $this->createMock(QueueCommandEntity::class);
        $entity
            ->expects($this->once())
            ->method('setStatus')
            ->with(States::FATAL);

        $doctrine = $this->createDoctrine($entity);
        $this->executeProcessor($doctrine, null);

        $this->assertTrue(true);
    }

    public function testStack(): void
    {
        $stack = new ArrayObject();

        $entity = $this->createStackMock($stack, QueueCommandEntity::class);

        $repository = $this->createRepository($entity);
        $manager = $this->createStackMock($stack, ObjectManager::class);

        $manager
            ->expects($this->once())
            ->method('getRepository')
            ->with(QueueCommandEntity::class)
            ->willReturn($repository);
        $doctrine = $this->createStackMock($stack, ManagerRegistry::class);

        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(QueueCommandEntity::class)
            ->willReturn($manager);

        $logger = $this->createStackMock($stack, LoggerInterface::class);
        $lockProvider = $this->createStackMock($stack, LockProvider::class);
        $lock = $this->createStackMock($stack, LockInterface::class);
        $lock
            ->expects($this->once())
            ->method('acquire')
            ->with(false)// test correct argument also
            ->willReturn(true);
        $lockProvider
            ->expects($this->once())
            ->method('create')
            ->willReturn($lock);

        $eventDispatcher = $this->createStackMock($stack, EventDispatcherInterface::class);


        $this->executeProcessor($doctrine, $logger, $lockProvider, $eventDispatcher);

        $expected = [
            ManagerRegistry::class . ':getManagerForClass',
            ObjectManager::class . ':getRepository',
            LockProvider::class . ':create',
            LockInterface::class . ':acquire',
            QueueCommandEntity::class . ':getId',
            LoggerInterface::class . ':error',
            LockInterface::class . ':release',
            QueueCommandEntity::class . ':setStatus',
            EventDispatcherInterface::class . ':dispatch',
            ObjectManager::class . ':flush',
            ObjectManager::class . ':clear',
        ];

        $this->assertArray($expected, $stack);
    }

    public function testLockAcquire(): void
    {

        $id = rand();
        $entity1 = $this->createMock(QueueCommandEntity::class);
        $entity2 = $this->createConfiguredMock(QueueCommandEntity::class, [
            'getId' => $id
        ]);
        $entity2
            ->expects($this->any())
            ->method('setStatus')
            ->with(States::FATAL);
        // set up objectRepository
        $data = [$entity1, $entity2];
        $values = [0, 1, 2];
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository
            ->expects($this->any())
            ->method('findBy')
            ->with(
                ['status' => States::PROCESSING],
                ['id' => 'ASC'],
                1,
                $this->callback(function (int $offset) use (&$values) {

                    return array_shift($values) === $offset;

                }))
            ->willReturnCallback(function () use (&$data) {
                return [array_shift($data)];
            });
        $manager = $this->createConfiguredMock(ObjectManager::class, [
            'getRepository' => $objectRepository

        ]);

        $doctrine = $this->createConfiguredMock(ManagerRegistry::class, [
            'getManagerForClass' => $manager
        ]);

        // set up lock provider
        $lock1 = $this->createMock(LockInterface::class);
        $lock1->expects($this->once())
            ->method('acquire')
            ->with(false)
            ->willReturn(false);
        $lock1
            ->expects($this->never())
            ->method('release');
        $lock2 = $this->createMock(LockInterface::class);
        $lock2->expects($this->once())
            ->method('acquire')
            ->with(false)
            ->willReturn(true);
        $lock2
            ->expects($this->once())
            ->method('release');

        $lockProvider = $this->createMock(LockProvider::class);
        $lockProvider
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [$entity1, $lock1],
                [$entity2, $lock2]
            ]);
        // set up logger
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(1))
            ->method('error')
            ->withConsecutive(
                [
                    'Found crashed worker',
                    [
                        'command_id' => $id,
                    ]
                ]
            );
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (Event $event) use ($entity2) {

                return
                    $event->getState() === Events::EXECUTE_FATAL
                    and
                    $event->getQueueCommand() === $entity2;
            }));

        $this->executeProcessor($doctrine, $logger, $lockProvider, $eventDispatcher);
    }

}