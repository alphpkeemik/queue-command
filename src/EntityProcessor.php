<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use DateTime;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @author mati.andreas@ambientia.ee
 */
class EntityProcessor
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EventDispatcherInterface $eventDispatcher, ContainerInterface $container, LoggerInterface $logger)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
        $this->logger = $logger;
    }

    public function process(QueueCommandEntity $command, QueueRepository $repository): void
    {

        $command->setStarted(new DateTime());
        $command->setStatus(States::PROCESSING);
        $repository->flush($command);

        $this->dispatchEvent(Events::EXECUTE_STARTED, $command);
        try {
            $service = $this->container->get($command->getService());
            $args = $command->getArguments();

            $message = $service->execute(... $args);
            if ($message) {
                $command->setMessage($message);
            }
            $command->setStatus(States::FINISHED);
            $command->setEnded(new DateTime());
            $this->dispatchEvent(Events::EXECUTE_FINISHED, $command);
        } catch (Throwable $e) {
            $command->setStatus(States::FAILED);
            $this->logger->error('Queue command execute error', [
                'command_id' => $command->getId(),
                'message' => $e->getMessage()
            ]);
            $message = $e->getMessage();
            if ($message) {
                $command->setMessage($message);
            }
            $command->setEnded(new DateTime());
            $this->dispatchEvent(Events::EXECUTE_FAILED, $command);
        }


        $repository->flush($command);
    }

    private function dispatchEvent(string $state, QueueCommandEntity $command): void
    {
        $this->eventDispatcher->dispatch(new Event($command, $state));
    }
}