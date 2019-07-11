<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand;

use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
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

    public function process(QueueCommandEntity $command, ObjectManager $em): void
    {

        $command->setStarted(new DateTime());
        $command->setStatus(States::PROCESSING);
        $em->flush();

        $this->dispatchEvent(Events::EXECUTE_STARTED, $command);
        try {
            $service = $this->container->get($command->getService());
            $args = $command->getArguments();

            $message = $service->execute(... $args);
            $command->setStatus(States::FINISHED);
            $this->dispatchEvent(Events::EXECUTE_FINISHED, $command);
        } catch (Throwable $e) {
            $command->setStatus(States::FAILED);
            $this->logger->error('Queue command execute error', [
                'command_id' => $command->getId(),
                'message' => $e->getMessage()
            ]);
            $message = $e->getMessage();
            $this->dispatchEvent(Events::EXECUTE_FAILED, $command);
        }
        if ($message) {
            $command->setMessage($message);
        }
        $command->setEnded(new DateTime());
        if (!$em->contains($command)) {
            $em->merge($command);
        }
        $em->flush();
    }

    private function dispatchEvent(string $state, QueueCommandEntity $command): void
    {
        $this->eventDispatcher->dispatch(new Event($command, $state));
    }
}