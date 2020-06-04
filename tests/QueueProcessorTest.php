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
use Ambientia\QueueCommand\TimeProvider;
use ArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockInterface;

/**
 * @author mati.andreas@ambientia.ee
 */
class QueueProcessorTest extends TestCase
{
    use StackMockTrait;

    private function createDoctrine(Selectable $selectable = null)
    {

        if (!$selectable) {
            $selectable = $this->createSelectable();
        }
        $manager = $this->createConfiguredMock(ObjectManager::class, [
            'getRepository' => $selectable

        ]);
        $doctrine = $this->createConfiguredMock(ManagerRegistry::class, [
            'getManagerForClass' => $manager
        ]);

        return $doctrine;
    }

    private function createSelectable()
    {
        $entity = $this->createMock(QueueCommandEntity::class);
        $data = [
            $entity
        ];

        $selectable = $this->createMock(Selectable::class);
        $selectable
            ->expects($this->any())
            ->method('matching')
            ->willReturnCallback(function () use (&$data) {
                return new ArrayCollection([array_shift($data)]);
            });

        return $selectable;
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
        EntityProcessor $entityProcessor = null,
        LockProvider $lockProvider = null,
        TimeProvider $timeProvider = null,
        QueueCriteria $criteria = null,
        int $timeLimit = null
    ): void {
        if (!$doctrine) {
            $doctrine = $this->createDoctrine();
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
        $processor = new QueueProcessor($doctrine, $logger, $entityProcessor, $lockProvider, $timeProvider);
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
            QueueCriteria::class . ':getWhereExpression',
            QueueCriteria::class . ':getOrderings',
            TimeProvider::class . ':time',
            LockProvider::class . ':create',
            LockInterface::class . ':acquire',
            EntityProcessor::class . ':process',
            LockInterface::class . ':release',
            LoggerInterface::class . ':debug',
        ];

        $this->assertArray($expected, $stack);
    }

    public function testInvalidRepository(): void
    {
        $repository = $this->createMock(ObjectRepository::class);
        $manager = $this->createConfiguredMock(ObjectManager::class, [
            'getRepository' => $repository
        ]);
        $doctrine = $this->createConfiguredMock(ManagerRegistry::class, [
            'getManagerForClass' => $manager
        ]);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Only %s repository supported', Selectable::class
        ));
        $this->executeProcessor($doctrine);
    }

    public function testRepositoryCall(): void
    {
        $orderings = [uniqid() => 'ASC'];
        $expression = $this->createMock(Expression::class);
        $criteria = $this->createConfiguredMock(QueueCriteria::class, [
            'getOrderings' => $orderings,
            'getWhereExpression' => $expression,
        ]);
        $selectable = $this->createMock(Selectable::class);
        $collection = $this->createMock(Collection::class);
        $selectable
            ->expects($this->exactly(1))
            ->method('matching')
            ->with($this->callback(function (Criteria $innerCriteria) use ($orderings, $expression) {
                return
                    $innerCriteria->getOrderings() === $orderings
                    and
                    $innerCriteria->getWhereExpression() === $expression
                    and
                    $innerCriteria->getFirstResult() === 0
                    and
                    $innerCriteria->getMaxResults() === 1;
            }))
            ->willReturn($collection);
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(1))
            ->method('getRepository')
            ->with(QueueCommandEntity::class)
            ->willReturn($selectable);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine
            ->expects($this->exactly(1))
            ->method('getManagerForClass')
            ->with(QueueCommandEntity::class)
            ->willReturn($manager);

        $this->executeProcessor($doctrine, null, null, null, null, $criteria);
    }

    public function testTimeLimit(): void
    {
        $data = [0, 21, 22];
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
                        'elapsed' => $data[2],
                    ]
                ],
                [
                    'Processed command count',
                    [
                        'count' => 0
                    ]
                ]
            );

        $timeProvider = $this->createMock(TimeProvider::class);

        $timeProvider
            ->expects($this->exactly(3))
            ->method('time')
            ->willReturnCallback(function () use (&$data) {
                return array_shift($data);
            });
        $lockProvider = $this->createMock(LockProvider::class);
        $lockProvider
            ->expects($this->never())
            ->method('create');

        $this->executeProcessor(null, $logger, null, $lockProvider, $timeProvider, null, $timeLimit);
    }

    public function testLockAcquire(): void
    {

        $id = rand();
        $entity1 = $this->createConfiguredMock(QueueCommandEntity::class, [
            'getId' => $id
        ]);
        $entity2 = $this->createMock(QueueCommandEntity::class);

        // set up selectable
        $data = [$entity1, $entity2];
        $values = [[0, 1], [1, 1], [0, 1]];
        $selectable = $this->createMock(Selectable::class);
        $selectable
            ->expects($this->any())
            ->method('matching')
            ->with($this->callback(function (Criteria $criteria) use (&$values) {
                list($a, $b) = array_shift($values);

                return
                    $criteria->getFirstResult() === $a
                    and
                    $criteria->getMaxResults() === $b;
            }))
            ->willReturnCallback(function () use (&$data) {
                return new ArrayCollection([array_shift($data)]);
            });

        $doctrine = $this->createDoctrine($selectable);


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

        $this->executeProcessor($doctrine, $logger, null, $lockProvider);
    }

}