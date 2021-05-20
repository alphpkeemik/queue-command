<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\QueueCommandEntity;
use Ambientia\QueueCommand\QueueCriteria;
use Ambientia\QueueCommand\QueueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

class QueueRepositoryTest extends TestCase
{
    use DoctrineTestTrait;

    public function testGetNextToExecuteAcceptance(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(ObjectManager::class);
        $repo = $this->createMock(Selectable::class);
        $criteria = $this->createConfiguredMock(QueueCriteria::class, [
            'getWhereExpression' => $this->createMock(Expression::class),
            'getOrderings' => [uniqid()],
        ]);
        $offset = rand();
        $expected = $this->createMock(QueueCommandEntity::class);

        $mr
            ->method('getManagerForClass')
            ->with(QueueCommandEntity::class)
            ->willReturn($em);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(QueueCommandEntity::class)
            ->willReturn($repo);
        $repo
            ->expects($this->once())
            ->method('matching')
            ->with(new Criteria(
                $criteria->getWhereExpression(),
                $criteria->getOrderings(),
                $offset, 1
            ))
            ->willReturn(new ArrayCollection([
                $expected
            ]));


        $service = new QueueRepository(
            $mr
        );

        $actual = $service->getNextToExecute($criteria, $offset);
        static::assertEquals($expected, $actual);

    }

    public function testGetNextToExecuteReturnsNull(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(ObjectManager::class);
        $repo = $this->createMock(Selectable::class);
        $criteria = $this->createConfiguredMock(QueueCriteria::class, [
            'getWhereExpression' => $this->createMock(Expression::class),
            'getOrderings' => [uniqid()],
        ]);
        $offset = rand();
        $expected = null;

        $mr
            ->method('getManagerForClass')
            ->with(QueueCommandEntity::class)
            ->willReturn($em);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(QueueCommandEntity::class)
            ->willReturn($repo);
        $repo
            ->expects($this->once())
            ->method('matching')
            ->with(new Criteria(
                $criteria->getWhereExpression(),
                $criteria->getOrderings(),
                $offset, 1
            ))
            ->willReturn(new ArrayCollection([]));


        $service = new QueueRepository(
            $mr
        );

        $actual = $service->getNextToExecute($criteria, $offset);
        static::assertEquals($expected, $actual);
    }

    public function testGetNextToExecuteBadRepo(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(ObjectManager::class);
        $repo = $this->createMock(ObjectRepository::class);
        $criteria = $this->createMock(QueueCriteria::class);
        $offset = rand();

        $mr
            ->method('getManagerForClass')
            ->with(QueueCommandEntity::class)
            ->willReturn($em);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(QueueCommandEntity::class)
            ->willReturn($repo);


        $service = new QueueRepository(
            $mr
        );
        $this->expectException(\LogicException::class);
        $service->getNextToExecute($criteria, $offset);

    }


    public function testFlush(): void
    {
        $doctrine = $this->createDoctrine($log = new SqlLog);
        $entity = new QueueCommandEntity(
            uniqid(), [
            uniqid()
        ],
            uniqid(),
            new \DateTime()
        );
        $objectManager = $doctrine->getManagerForClass(QueueCommandEntity::class);
        $objectManager->persist($entity);
        $objectManager->flush();
        $objectManager->clear();

        $entity->setStatus($status = uniqid('ended '));
        $entity->setMessage($message = uniqid('message '));
        $entity->setStarted($started = new \DateTime());
        $entity->setEnded($ended = new \DateTime());


        $service = new QueueRepository(
            $doctrine
        );
        $log->reset();
        $service->flush($entity);
        self::assertCount(1, $log->log);
        self::assertMatchesRegularExpression('/^UPDATE/', $log->log[0]);

    }


}
