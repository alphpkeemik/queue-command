<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\HashGenerator;
use Ambientia\QueueCommand\QueueCommandEntity;
use Ambientia\QueueCommand\Repository;
use Ambientia\Toolset\Test\DoctrineMockTrait;
use DateTime;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    private function createDoctrine(): ManagerRegistry
    {
        $em = DoctrineTestHelper::createTestEntityManager();
        $st = new SchemaTool($em);
        $st->updateSchema($em->getMetadataFactory()->getAllMetadata(), false);

        $mr = $this->createMock(ManagerRegistry::class);
        $mr
            ->method('getManagerForClass')
            ->with(QueueCommandEntity::class)
            ->willReturn($em);

        return $mr;
    }

    public function testCountQueuedByService(): void
    {

        $service = new Repository(
            $this->createDoctrine(),
            new HashGenerator()
        );
        $serviceName = uniqid();

        $actual = $service->countQueuedByService($serviceName);
        static::assertEquals(0, $actual);

        $service->insertIfNotExists($serviceName);
        $service->flushAndClear();
        $actual = $service->countQueuedByService($serviceName);
        static::assertEquals(1, $actual);

        $service->insertIfNotExists($serviceName, new DateTime());
        $service->flushAndClear();
        $actual = $service->countQueuedByService($serviceName);
        static::assertEquals(1, $actual);

        $service->insertIfNotExists($serviceName, null, rand());
        $service->flushAndClear();
        $actual = $service->countQueuedByService($serviceName);
        static::assertEquals(1, $actual);

        $service->insertIfNotExists($serviceName, null, null, uniqid());
        $service->flushAndClear();
        $actual = $service->countQueuedByService($serviceName);
        static::assertEquals(2, $actual);
    }

    public function testGetQueuedByServiceAndArguments(): void
    {

        $service = new Repository(
            $this->createDoctrine(),
            new HashGenerator()
        );
        $serviceName = uniqid();

        $args = [uniqid(), [uniqid() => uniqid()]];
        $actual = $service->getQueuedByServiceAndArguments($serviceName, $args);
        static::assertCount(0, $actual);

        // real
        $service->insertIfNotExists($serviceName, null, null, ... $args);
        $service->insertIfNotExists($serviceName, null, 1,  ... $args);
        $service->insertIfNotExists($serviceName, new DateTime(), null,  ... $args);
        $service->insertIfNotExists($serviceName, new DateTime(), -1,  ... $args);

        //other
        $service->insertIfNotExists($serviceName, null, null, ['other args' => uniqid()], uniqid('other string arg'));

        $service->flushAndClear();
        $actual = $service->getQueuedByServiceAndArguments($serviceName, $args);
        static::assertCount(4, $actual);

    }

    /**
     * @dataProvider dataInsert
     *
     * @param string        $service
     * @param DateTime|null $ttl
     * @param int|null      $priority
     * @param mixed         ...$arguments
     */
    public function testInsert(
        string $service,
        DateTime $ttl = null,
        int $priority = null,
        ...$arguments
    ): void {
        $events = [
            'postFlush',
            'onClear',
            'postPersist',
        ];
        $listener = new class() {
            public $calls = [];

            public function __call($name, $arguments)
            {
                $this->calls[] = [$name, $arguments];
            }
        };

        $doctrine = $this->createDoctrine();
        $em = $doctrine->getManagerForClass(QueueCommandEntity::class);
        $em->getEventManager()->addEventListener($events, $listener);

        $repository = new Repository(
            $doctrine,
            new HashGenerator()
        );

        //stage one, none queued
        $listener->calls = [];
        $repository->insert($service, $ttl, $priority, ...$arguments);

        $repository->flushAndClear();
        self::assertCount(3, $listener->calls);
        self::assertSame('postPersist', $listener->calls[0][0]);
        self::assertSame('postFlush', $listener->calls[1][0]);
        self::assertSame('onClear', $listener->calls[2][0]);

        //stage two, service queued
        $listener->calls = [];
        $result = $repository->insertIfNotExists($service, $ttl, $priority, ...$arguments);
        static::assertFalse($result);

        $repository->flushAndClear();
        self::assertCount(0, $listener->calls);
    }

    /**
     * @dataProvider dataInsert
     *
     * @param string        $service
     * @param DateTime|null $ttl
     * @param int|null      $priority
     * @param mixed         ...$arguments
     */
    public function testInsertIfNotExists(
        string $service,
        DateTime $ttl = null,
        int $priority = null,
        ...$arguments
    ): void {
        $events = [
            'postFlush',
            'onClear',
            'postPersist',
        ];
        $listener = new class() {
            public $calls = [];

            public function __call($name, $arguments)
            {
                $this->calls[] = [$name, $arguments];
            }
        };

        $doctrine = $this->createDoctrine();
        $em = $doctrine->getManagerForClass(QueueCommandEntity::class);
        $em->getEventManager()->addEventListener($events, $listener);

        $repository = new Repository(
            $doctrine,
            new HashGenerator()
        );

        //stage one, none queued
        $listener->calls = [];
        $result = $repository->insertIfNotExists($service, $ttl, $priority, ...$arguments);
        static::assertTrue($result);

        $repository->flushAndClear();
        self::assertCount(3, $listener->calls);
        self::assertSame('postPersist', $listener->calls[0][0]);
        self::assertSame('postFlush', $listener->calls[1][0]);
        self::assertSame('onClear', $listener->calls[2][0]);

        //stage two, service queued
        $listener->calls = [];
        $result = $repository->insertIfNotExists($service, $ttl, $priority, ...$arguments);
        static::assertFalse($result);

        $repository->flushAndClear();
        self::assertCount(0, $listener->calls);
    }

    public function dataInsert(): Generator
    {
        yield 'only service' => [
            sprintf('App\\%s\\%s', uniqid(), uniqid()),
        ];

        yield 'service and ttl' => [
            uniqid(),
            new DateTime('now'),
        ];

        yield 'service and priority' => [
            uniqid(),
            null,
            rand(),
        ];

        yield 'service, ttl and priority' => [
            uniqid(),
            new DateTime('now'),
            rand(),
        ];

        yield 'service, priority, and two arguments' => [
            uniqid(),
            new DateTime('now'),
            null,
            uniqid(),
            uniqid(),
        ];
    }


}
