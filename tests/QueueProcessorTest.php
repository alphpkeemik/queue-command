<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\EntityProcessor;
use Ambientia\QueueCommand\LockProvider;
use Ambientia\QueueCommand\QueueCommand;
use Ambientia\QueueCommand\QueueCommandEntity;
use Ambientia\QueueCommand\QueueCriteria;
use Ambientia\QueueCommand\QueueProcessor;
use Ambientia\QueueCommand\QueueRepository;
use Ambientia\QueueCommand\TimeProvider;
use ArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockInterface;

/**
 * @author mati.andreas@ambientia.ee
 */
class QueueProcessorTest extends TestCase
{
    use StackMockTrait;


    private function createQueueRepository()
    {
        $entity = $this->createMock(QueueCommandEntity::class);
        $data = [
            $entity
        ];

        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository
            ->expects($this->any())
            ->method('getNextToExecute')
            ->willReturnCallback(function () use (&$data) {
                return array_shift($data);
            });

        return $queueRepository;
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
        QueueRepository $queueRepository = null,
        LoggerInterface $logger = null,
        EntityProcessor $entityProcessor = null,
        LockProvider $lockProvider = null,
        TimeProvider $timeProvider = null,
        QueueCriteria $criteria = null,
        int $timeLimit = null
    ): void {
        if (!$queueRepository) {
            $queueRepository = $this->createQueueRepository();
        }
        if (!$logger) {
            $logger = $this->createMock(LoggerInterface::class);
        }
        if (!$entityProcessor) {
            $entityProcessor = $this->createMock(EntityProcessor::class);
        }
        if (!$lockProvider) {
            $lockProvider = $this->createLockProvider();
        }
        if (!$timeProvider) {
            $timeProvider = $this->createMock(TimeProvider::class);
        }
        if (!$criteria) {
            $criteria = $this->createMock(QueueCriteria::class);
        }
        $processor = new QueueProcessor($queueRepository, $logger, $entityProcessor, $lockProvider, $timeProvider);
        $processor->process($criteria, $timeLimit);
    }

    public function testAcceptance(): void
    {
        $entityProcessor = $this->createMock(EntityProcessor::class);
        $entityProcessor
            ->expects($this->once())
            ->method('process');

        $this->executeProcessor(null, null, $entityProcessor);

        $this->assertTrue(true);
    }

    public function testStack(): void
    {
        $stack = new ArrayObject();

        $logger = $this->createStackMock($stack, LoggerInterface::class);
        $entityProcessor = $this->createStackMock($stack, EntityProcessor::class);
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

        $timeProvider = $this->createStackMock($stack, TimeProvider::class);
        $timeProvider
            ->expects($this->any())
            ->method('time')
            ->willReturnCallback(function () {
                return time();
            });


        $criteria = $this->createStackMock($stack, QueueCriteria::class);

        $this->executeProcessor(null, $logger, $entityProcessor, $lockProvider, $timeProvider, $criteria, 10);
        $expected = [
            TimeProvider::class . ':time',
            LockProvider::class . ':create',
            LockInterface::class . ':acquire',
            EntityProcessor::class . ':process',
            LockInterface::class . ':release',
            TimeProvider::class . ':time',
            LoggerInterface::class . ':debug',
        ];

        $this->assertArray($expected, $stack);
    }

    public function testRepositoryCall(): void
    {
        $orderings = [uniqid() => 'ASC'];
        $expression = $this->createMock(Expression::class);
        $criteria = $this->createConfiguredMock(QueueCriteria::class, [
            'getOrderings' => $orderings,
            'getWhereExpression' => $expression,
        ]);
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository
            ->expects($this->once())
            ->method('getNextToExecute')
            ->with($criteria, 0);


        $this->executeProcessor($queueRepository, null, null, null, null, $criteria);
    }

    public function testTimeLimit(): void
    {
        $data = [0, 21];
        $timeLimit = rand(10, 20);
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [
                    'Breaking after time limit',
                    [
                        'timeLimit' => $timeLimit,
                        'elapsed' => $data[1],
                    ]
                ],
                [
                    'Processed command count',
                    [
                        'count' => 1
                    ]
                ]
            );

        $timeProvider = $this->createMock(TimeProvider::class);

        $timeProvider
            ->expects($this->exactly(2))
            ->method('time')
            ->willReturnCallback(function () use (&$data) {
                return array_shift($data);
            });
        $entity = $this->createMock(QueueCommandEntity::class);
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository
            ->expects($this->once())
            ->method('getNextToExecute')
            ->willReturn($entity);

        $lock = $this->createConfiguredMock(LockInterface::class, [
            'acquire' => true
        ]);
        $lockProvider = $this->createMock(LockProvider::class);
        $lockProvider
            ->expects($this->once())
            ->method('create')
            ->willReturn($lock);

        $this->executeProcessor($queueRepository, $logger, null, $lockProvider, $timeProvider, null, $timeLimit);
    }

    public function testLockAcquire(): void
    {

        $id = rand();
        $entity1 = $this->createConfiguredMock(QueueCommandEntity::class, [
            'getId' => $id
        ]);
        $entity2 = $this->createMock(QueueCommandEntity::class);

        $criteria = $this->createMock(QueueCriteria::class);

        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository
            ->expects($this->any())
            ->method('getNextToExecute')
            ->withConsecutive(
                [$criteria, 0],
                [$criteria, 1],
                [$criteria, 0]
            )
            ->willReturnOnConsecutiveCalls(
                $entity1,
                $entity2
            );



        // set up lock provider
        $lock1 = $this->createConfiguredMock(LockInterface::class, [
            'acquire' => false
        ]);
        $lock2 = $this->createConfiguredMock(LockInterface::class, [
            'acquire' => true
        ]);
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
            ->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [
                    'Unable acquire lock',
                    [
                        'command_id' => $id,
                    ]
                ],
                [
                    'Processed command count',
                    [
                        'count' => 1
                    ]
                ]
            );

        $this->executeProcessor(
            $queueRepository,
            $logger,
            null,
            $lockProvider,
            null,
            $criteria
        );
    }

}