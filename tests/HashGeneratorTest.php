<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\HashGenerator;
use PHPUnit\Framework\TestCase;

class HashGeneratorTest extends TestCase
{

    public function testGenerate(): void
    {
        $script = new HashGenerator();
        $service = uniqid();
        $arguments = [uniqid()];
        $hash1 = $script->generate($service, $arguments);
        $hash2 = $script->generate($service, $arguments);

        $this->assertEquals($hash1, $hash2);
    }


}
