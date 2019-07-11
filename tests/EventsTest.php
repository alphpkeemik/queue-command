<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\Events;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @author mati.andreas@ambientia.ee
 */
class EventsTest extends TestCase
{
    private static $c;

    public static function setUpBeforeClass(): void
    {
        $rc = new ReflectionClass(Events::class);
        static::$c = $rc->getConstants();
    }

    /** @dataProvider provideHasConstantAndConstantSame */
    public function testHasConstantAndConstantSame(string $constant, string $value): void
    {
        $this->assertArrayHasKey($constant, static::$c);
        $this->assertSame($value, static::$c[$constant]);
    }

    public function provideHasConstantAndConstantSame()
    {
        yield ['EXECUTE_STARTED', 'queue-command.execute.started'];
        yield ['EXECUTE_FINISHED', 'queue-command.execute.finished'];
        yield ['EXECUTE_FATALED', 'queue-command.execute.fataled'];
        yield ['EXECUTE_FAILED', 'queue-command.execute.failed'];
    }

    public function testConstantCount(): void
    {
        $this->assertCount(4, static::$c);
    }
}
