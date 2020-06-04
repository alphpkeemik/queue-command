<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\QueueCommandEntity;
use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use TypeError;

/**
 * @author mati.andreas@ambientia.ee
 */
class EntityTest extends TestCase
{
    public function testId(): void
    {
        $entity = new QueueCommandEntity();
        $this->expectException(TypeError::class);
        $entity->getId();
    }

    public function tesService(): void
    {
        $entity = new QueueCommandEntity();
        $this->expectException(TypeError::class);
        $entity->getService();
    }

    public function testValues(): void
    {

        $entity = new QueueCommandEntity();

        $this->assertSame([], $entity->getArguments());
        $this->assertNull($entity->getTtl());
        $this->assertNull($entity->getStatus());
        $this->assertNull($entity->getStarted());
        $this->assertNull($entity->getEnded());
        $this->assertNull($entity->getMessage());
        $this->assertEquals(0, $entity->getPriority());

        $id = rand();

        $rc = new ReflectionProperty(QueueCommandEntity::class, 'id');
        $rc->setAccessible(true);
        $rc->setValue($entity, $id);
        $this->assertEquals($id, $entity->getId());

        $service = uniqid();
        $entity->setService($service);
        $this->assertEquals($service, $entity->getService());

        $arguments = [uniqid()];
        $entity->setArguments($arguments);
        $this->assertEquals($arguments, $entity->getArguments());

        $ttl = new DateTime();
        $entity->setTtl($ttl);
        $this->assertEquals($ttl, $entity->getTtl());

        $status = uniqid();
        $entity->setStatus($status);
        $this->assertEquals($status, $entity->getStatus());

        $started = new DateTime();
        $entity->setStarted($started);
        $this->assertEquals($started, $entity->getStarted());

        $ended = new DateTime();
        $entity->setEnded($ended);
        $this->assertEquals($ended, $entity->getEnded());

        $message = uniqid();
        $entity->setMessage($message);
        $this->assertEquals($message, $entity->getMessage());

        $priority = rand();
        $entity->setPriority($priority);
        $this->assertEquals($priority, $entity->getPriority());

    }


}
