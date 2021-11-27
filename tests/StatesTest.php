<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\States;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @author mati.andreas@ambientia.ee
 */
class StatesTest extends TestCase
{
    private static $c;

    public static function setUpBeforeClass(): void
    {
        $rc = new ReflectionClass(States::class);
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
        yield ['PROCESSING', 'processing'];
        yield ['FAILED', 'failed'];
        yield ['FINISHED', 'finished'];
        yield ['FATAL', 'fatal'];
        yield ['CANCELED', 'canceled'];
    }

    public function testConstantCount(): void
    {
        $this->assertCount(5, static::$c);
    }
}
