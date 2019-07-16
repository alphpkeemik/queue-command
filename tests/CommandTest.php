<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand\Tests;


use Ambientia\QueueCommand\CrashedProcessor;
use Ambientia\QueueCommand\CriteriaBuilder;
use Ambientia\QueueCommand\ExecuteCommand;
use Ambientia\QueueCommand\FlockStoreCleaner;
use Ambientia\QueueCommand\QueueCommand;
use Ambientia\QueueCommand\QueueCriteria;
use Ambientia\QueueCommand\QueueProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandTest extends TestCase
{

    public function testTest()
    {
        $crashedProcessor = $this->createMock(CrashedProcessor::class);
        $crashedProcessor
            ->expects($this->once())
            ->method('process');
        $flockStoreCleaner = $this->createMock(FlockStoreCleaner::class);
        $flockStoreCleaner
            ->expects($this->once())
            ->method('process');
        $queueProcessor = $this->createMock(QueueProcessor::class);
        $timeLimit = rand();
        $criteria = $this->createMock(QueueCriteria::class);
        $queueProcessor
            ->expects($this->once())
            ->method('process')
            ->with($criteria, $timeLimit);
        $criteriaBuilder = $this->createMock(CriteriaBuilder::class);
        $criteriaBuilder
            ->expects($this->once())
            ->method('build')
            ->willReturn($criteria);

        $command = new ExecuteCommand();
        $command->setCrashedProcessor($crashedProcessor);
        $command->setFlockStoreCleaner($flockStoreCleaner);
        $command->setQueueProcessor($queueProcessor);
        $command->setCriteriaBuilder($criteriaBuilder);

        $inputInterface = $this->createMock(InputInterface::class);
        $inputInterface
            ->expects($this->once())
            ->method('getOption')
            ->with('time-limit')
            ->willReturn($timeLimit);
        $outputInterface = $this->createMock(OutputInterface::class);

        $command->run($inputInterface, $outputInterface);

        $this->assertSame('ambientia:queue-command:execute', $command->getName());


    }
}
