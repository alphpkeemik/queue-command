<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\Event;
use Ambientia\QueueCommand\QueueCommandEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event as Base;

/**
 * @author mati.andreas@ambientia.ee
 */
class EventTest extends TestCase
{

    public function testTest(): void
    {
        $entity = new QueueCommandEntity();

        $state = uniqid();

        $event = new Event($entity, $state);
        $this->assertEquals($entity, $event->getQueueCommand());
        $this->assertEquals($state, $event->getState());

    }


}
