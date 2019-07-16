<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\TimeProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author mati.andreas@ambientia.ee
 */
class TimeProviderTest extends TestCase
{

    public function testAcceptance(): void
    {
        $provider = new TimeProvider();

        $this->assertSame(time(), $provider->time());
    }


}