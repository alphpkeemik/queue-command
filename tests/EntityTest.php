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
        $entity = new QueueCommandEntity(
            uniqid(), [], uniqid(), null
        );
        $this->expectException(TypeError::class);
        $entity->getId();
    }

    public function testTtl(): void
    {
        $entity = new QueueCommandEntity(
            uniqid(), [], uniqid(), null
        );
        $this->assertNull($entity->getTtl());
    }

    public function testValues(): void
    {

        $service = uniqid();
        $arguments = [uniqid()];
        $hash = uniqid();
        $ttl = new DateTime();
        $entity = new QueueCommandEntity(
            $service, $arguments, $hash, $ttl
        );

        $this->assertSame($arguments, $entity->getArguments());
        $this->assertSame($ttl, $entity->getTtl());
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
