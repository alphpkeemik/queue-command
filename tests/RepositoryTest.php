<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\HashGenerator;
use Ambientia\QueueCommand\Repository;
use Ambientia\Toolset\Test\DoctrineMockTrait;
use DateTime;
use Generator;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{

    use DoctrineTestTrait;

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
        $actual = $service->countQueuedByService($serviceName);
        static::assertEquals(1, $actual);

        $service->insertIfNotExists($serviceName, new DateTime());
        $actual = $service->countQueuedByService($serviceName);
        static::assertEquals(1, $actual);

        $service->insertIfNotExists($serviceName, null, rand());
        $actual = $service->countQueuedByService($serviceName);
        static::assertEquals(1, $actual);

        $service->insertIfNotExists($serviceName, null, null, uniqid());
        $actual = $service->countQueuedByService($serviceName);
        static::assertEquals(2, $actual);
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


        $doctrine = $this->createDoctrine($log = new SqlLog);

        $repository = new Repository(
            $doctrine,
            new HashGenerator()
        );

        $repository->insert($service, $ttl, $priority, ...$arguments);

        self::assertCount(1, $log->log);
        self::assertMatchesRegularExpression('/^INSERT/', $log->log[0]);
        $log->reset();

        //stage two, service queued
        $result = $repository->insertIfNotExists($service, $ttl, $priority, ...$arguments);
        static::assertFalse($result);
        self::assertCount(1, $log->log);
        self::assertMatchesRegularExpression('/^SELECT/', $log->log[0]);
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
        $doctrine = $this->createDoctrine($log = new SqlLog);

        $repository = new Repository(
            $doctrine,
            new HashGenerator()
        );

        $result = $repository->insertIfNotExists($service, $ttl, $priority, ...$arguments);
        static::assertTrue($result);
        self::assertCount(2, $log->log);
        self::assertMatchesRegularExpression('/^SELECT/', $log->log[0]);
        self::assertMatchesRegularExpression('/^INSERT/', $log->log[1]);

        //stage two, service queued
        $log->reset();
        $result = $repository->insertIfNotExists($service, $ttl, $priority, ...$arguments);
        static::assertFalse($result);
        self::assertCount(1, $log->log);
        self::assertMatchesRegularExpression('/^SELECT/', $log->log[0]);
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
