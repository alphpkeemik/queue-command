<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\EntityProcessor;
use Ambientia\QueueCommand\Event;
use Ambientia\QueueCommand\Events;
use Ambientia\QueueCommand\QueueCommandEntity;
use Ambientia\QueueCommand\States;
use ArrayObject;
use Closure;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * @author mati.andreas@ambientia.ee
 */
class EntityProcessorTest extends TestCase
{

    use StackMockTrait;

    private function createCommand(Closure $closure = null)
    {
        $command = new class
        {
            var $__closure__;

            function execute()
            {
                if ($this->__closure__) {
                    return ($this->__closure__)();
                }
            }
        };
        $command->__closure__ = $closure;

        return $command;
    }


    private function executeProcessor(
        EventDispatcherInterface $evenDispatcher = null,
        ContainerInterface $container = null,
        LoggerInterface $logger = null,
        QueueCommandEntity $entity = null,
        ObjectManager $objectManager = null
    ): void {
        if (!$evenDispatcher) {
            $evenDispatcher = $this->createMock(EventDispatcherInterface::class);
        }
        if (!$container) {
            $command = $this->createCommand();
            $container = $this->createConfiguredMock(ContainerInterface::class, [
                'get' => $command
            ]);
        }
        if (!$logger) {
            $logger = $this->createMock(LoggerInterface::class);
        }
        if (!$entity) {
            $entity = $this->createMock(QueueCommandEntity::class);
        }
        if (!$objectManager) {
            $objectManager = $this->createMock(ObjectManager::class);
        }

        $processor = new EntityProcessor($evenDispatcher, $container, $logger);
        $processor->process($entity, $objectManager);
    }

    public function testAcceptance(): void
    {
        $this->executeProcessor();
        $this->assertTrue(true);
    }

    public function testStackSuccess(): void
    {
        $stack = new ArrayObject();
        $entity = $this->createStackMock($stack, QueueCommandEntity::class);
        $evenDispatcher = $this->createStackMock($stack, EventDispatcherInterface::class);
        $objectManager = $this->createStackMock($stack, ObjectManager::class);
        $logger = $this->createStackMock($stack, LoggerInterface::class);
        $this->executeProcessor($evenDispatcher, null, $logger, $entity, $objectManager);
        $expected = [
            QueueCommandEntity::class . ':setStarted',
            QueueCommandEntity::class . ':setStatus',
            ObjectManager::class . ':flush',
            EventDispatcherInterface::class . ':dispatch',
            QueueCommandEntity::class . ':getService',
            QueueCommandEntity::class . ':getArguments',
            QueueCommandEntity::class . ':setStatus',
            EventDispatcherInterface::class . ':dispatch',
            QueueCommandEntity::class . ':setEnded',
            ObjectManager::class . ':contains',
            ObjectManager::class . ':merge',
            ObjectManager::class . ':flush',
        ];

        $this->assertArray($expected, $stack);
    }

    public function testStackFailure(): void
    {
        $stack = new ArrayObject();
        $entity = $this->createStackMock($stack, QueueCommandEntity::class);
        $evenDispatcher = $this->createStackMock($stack, EventDispatcherInterface::class);
        $container = $this->createStackMock($stack, ContainerInterface::class);
        $logger = $this->createStackMock($stack, LoggerInterface::class);
        $objectManager = $this->createStackMock($stack, ObjectManager::class);
        $this->executeProcessor($evenDispatcher, $container, $logger, $entity, $objectManager);
        $expected = [
            QueueCommandEntity::class . ':setStarted',
            QueueCommandEntity::class . ':setStatus',
            ObjectManager::class . ':flush',
            EventDispatcherInterface::class . ':dispatch',
            QueueCommandEntity::class . ':getService',
            ContainerInterface::class . ':get',
            QueueCommandEntity::class . ':getArguments',
            QueueCommandEntity::class . ':setStatus',
            QueueCommandEntity::class . ':getId',
            LoggerInterface::class . ':error',
            EventDispatcherInterface::class . ':dispatch',
            QueueCommandEntity::class . ':setMessage',
            QueueCommandEntity::class . ':setEnded',
            ObjectManager::class . ':contains',
            ObjectManager::class . ':merge',
            ObjectManager::class . ':flush',
        ];

        $this->assertArray($expected, $stack);
    }

    private function createEntityForCallStackValue(ArrayObject $stack)
    {
        $entity = $this->createMock(QueueCommandEntity::class);

        $entity
            ->expects($this->any())
            ->method($this->callback(function (string $name) use ($stack) {

                $stack->append($name);

                return (bool)preg_match('/^set/', $name);
            }))
            ->with($this->callback(function () use ($stack) {
                $args = func_get_args();
                if (count($args) and $args[0] instanceOf DateTime) {
                    $args[0] = $args[0]->format('Y-m-d h:i:s');
                }
                $stack->append(':' . implode(',', $args));

                return true;
            }));

        return $entity;
    }

    public function testEntitySuccess(): void
    {
        $stack = new ArrayObject();
        $entity = $this->createEntityForCallStackValue($stack);
        $message = uniqid();
        $command = $this->createCommand(function () use ($message) {
            sleep(1);

            return $message;
        });
        $container = $this->createConfiguredMock(ContainerInterface::class, [
            'get' => $command
        ]);
        $started = date(':Y-m-d h:i:s');
        $this->executeProcessor(null, $container, null, $entity);
        $expected = [
            'setStarted',
            $started,
            'setStatus',
            ':' . States::PROCESSING,
            'getService',
            'getArguments',
            'setStatus',
            ':' . States::FINISHED,
            'setMessage',
            ':' . $message,
            'setEnded',
            date(':Y-m-d h:i:s'),
        ];

        $this->assertArray($expected, $stack);
    }

    public function testEntityFailure(): void
    {
        $stack = new ArrayObject();

        $entity = $this->createEntityForCallStackValue($stack);
        $message = uniqid();
        $command = $this->createCommand(function () use ($message) {
            sleep(1);
            throw new Exception($message);
        });
        $container = $this->createConfiguredMock(ContainerInterface::class, [
            'get' => $command
        ]);
        $started = date(':Y-m-d h:i:s');
        $this->executeProcessor(null, $container, null, $entity);
        $expected = [
            'setStarted',
            $started,
            'setStatus',
            ':' . States::PROCESSING,
            'getService',
            'getArguments',
            'setStatus',
            ':' . States::FAILED,
            'getId',
            'setMessage',
            ':' . $message,
            'setEnded',
            date(':Y-m-d h:i:s'),
        ];
        $this->assertArray($expected, $stack);
    }


    public function testObjectManager(): void
    {
        $entity = $this->createMock(QueueCommandEntity::class);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->expects($this->once())
            ->method('contains')
            ->with($entity);
        $objectManager
            ->expects($this->once())
            ->method('merge')
            ->with($entity);

        $this->executeProcessor(null, null, null, $entity, $objectManager);
    }

    private function createEventDispatcherForCallStackValue(ArrayObject $stack, QueueCommandEntity $entity)
    {
        $evenDispatcher = $this->createMock(EventDispatcherInterface::class);

        $evenDispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->callback(function (Event $event) use ($stack, $entity) {
                    $stack->append($event->getState());

                    return $event->getQueueCommand() === $entity;
                })

            );

        return $evenDispatcher;
    }

    public function testEventDispatcherSuccess(): void
    {
        $stack = new ArrayObject();
        $entity = $this->createMock(QueueCommandEntity::class);
        $evenDispatcher = $this->createEventDispatcherForCallStackValue($stack, $entity);
        $this->executeProcessor($evenDispatcher, null, null, $entity);
        $expected = [
            Events::EXECUTE_STARTED,
            Events::EXECUTE_FINISHED,
        ];

        $this->assertArray($expected, $stack);
    }

    public function testEventDispatcherFailure(): void
    {
        $stack = new ArrayObject();
        $entity = $this->createMock(QueueCommandEntity::class);
        $evenDispatcher = $this->createEventDispatcherForCallStackValue($stack, $entity);
        $container = $this->createMock(ContainerInterface::class);
        $this->executeProcessor($evenDispatcher, $container, null, $entity);
        $expected = [
            Events::EXECUTE_STARTED,
            Events::EXECUTE_FAILED,
        ];

        $this->assertArray($expected, $stack);
    }

    public function testContainer(): void
    {
        $service = uniqid();
        $container = $this->createMock(ContainerInterface::class);
        $entity = $this->createConfiguredMock(QueueCommandEntity::class, [
            'getService' => $service
        ]);

        $container
            ->expects($this->once())
            ->method('get')
            ->with($service);

        $this->executeProcessor(null, $container, null, $entity);

    }

    public function testCommand(): void
    {
        $arguments = [uniqid(), new stdClass()];
        $command = $this->createMock(Command::class);
        $container = $this->createConfiguredMock(ContainerInterface::class, [
            'get' => $command
        ]);
        $entity = $this->createConfiguredMock(QueueCommandEntity::class, [
            'getArguments' => $arguments
        ]);

        $command
            ->expects($this->once())
            ->method('execute')
            ->with(... $arguments);

        $this->executeProcessor(null, $container, null, $entity);

    }

    public function testLogger(): void
    {
        $id = rand();
        $message = uniqid();
        $command = $this->createCommand(function () use ($message) {
            throw new Exception($message);
        });
        $container = $this->createConfiguredMock(ContainerInterface::class, [
            'get' => $command
        ]);
        $entity = $this->createConfiguredMock(QueueCommandEntity::class, [
            'getId' => $id
        ]);
        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once(1))
            ->method('error')
            ->with(
                'Queue command execute error',
                [
                    'command_id' => $id,
                    'message' => $message
                ]
            );

        $this->executeProcessor(null, $container, $logger, $entity);

    }

}
