<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand\Tests;

use ArrayObject;

/**
 * @author mati.andreas@ambientia.ee
 */
trait StackMockTrait
{
    private function createStackMock(ArrayObject $result, string $class)
    {
        $mockBuilder = $this->getMockBuilder($class)
            ->setMethodsExcept([]);

        $observer = $mockBuilder->getMock();

        $observer
            ->expects($this->any())
            ->method($this->callback(function (string $name) use ($class, $result) {

                $result->append("$class:$name");

                return true;
            }));


        return $observer;
    }


    private function assertArray(array $expected, ArrayObject $stack): void
    {
        $this->assertSame(
            implode("\n", $expected),
            implode("\n", $stack->getArrayCopy())
        );
    }

}