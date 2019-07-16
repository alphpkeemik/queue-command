<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\FlockStoreCleaner;
use Ambientia\QueueCommand\LockProvider;
use Ambientia\QueueCommand\QueueCommandEntity;
use Ambientia\QueueCommand\States;
use DateTime;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Lock\Store\FlockStore;

/**
 * @author mati.andreas@ambientia.ee
 */
class FlockStoreCleanerTest extends TestCase
{

    private $dir;

    public function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Ambientia_QueueCommand_FlockStoreCleanerTest';
        $fileSystem = new Filesystem();
        if (is_dir($this->dir)) {
            $fileSystem->remove($this->dir);
        }
        $fileSystem->mkdir($this->dir);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $fileSystem = new Filesystem();
        if (is_dir($this->dir)) {
            $fileSystem->remove($this->dir);
        }
        parent::tearDown();
    }


    private function createDoctrine(ObjectRepository $repository = null)
    {
        if (!$repository) {
            $repository = $this->createMock(ObjectRepository::class);
        }
        $manager = $this->createConfiguredMock(ObjectManager::class, [
            'getRepository' => $repository

        ]);

        $doctrine = $this->createConfiguredMock(ManagerRegistry::class, [
            'getManagerForClass' => $manager
        ]);

        return $doctrine;
    }

    private function createLockFile(): void
    {
        $id = rand();
        $entity = $this->createConfiguredMock(QueueCommandEntity::class, [
            'getId' => $id
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $store = new FlockStore($this->dir);
        $provider = new LockProvider($logger, $store);
        $lock = $provider->create($entity);
        $this->assertTrue($lock->acquire(false));
    }

    private function createTestDirFinder(): Finder
    {
        $finder = new Finder();
        $finder->files();
        $finder->in($this->dir);

        return $finder;
    }

    private function executeProcessor(
        ManagerRegistry $doctrine = null,
        LoggerInterface $logger = null,
        Filesystem $fileSystem = null
    ): void {
        if (!$doctrine) {
            $doctrine = $this->createDoctrine();
        }
        if (!$logger) {
            $logger = $this->createMock(LoggerInterface::class);
        }
        if (!$fileSystem) {
            $fileSystem = $this->createMock(Filesystem::class);
        }

        $processor = new FlockStoreCleaner($doctrine, $fileSystem, $logger, $this->dir);
        $processor->process();
    }

    public function testAcceptance(): void
    {
        $this->createLockFile();

        $this->executeProcessor(null, null, new Filesystem());
        $finder = $this->createTestDirFinder();
        $this->assertCount(0, $finder);
    }

    public function testRepository(): void
    {
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository
            ->expects($this->any())
            ->method('findBy')
            ->with(
                ['status' => States::PROCESSING],
                ['started' => 'ASC'],
                0,
                1)
            ->willReturn([]);
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(1))
            ->method('getRepository')
            ->with(QueueCommandEntity::class)
            ->willReturn($objectRepository);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine
            ->expects($this->exactly(1))
            ->method('getManagerForClass')
            ->with(QueueCommandEntity::class)
            ->willReturn($manager);

        $this->executeProcessor($doctrine);

    }

    public function testTime(): void
    {

        $this->createLockFile();
        sleep(1);
        $time = new DateTime();
        $this->createLockFile();

        $entity = $this->createConfiguredMock(QueueCommandEntity::class, [
            'getStarted' => $time
        ]);
        $objectRepository = $this->createConfiguredMock(ObjectRepository::class, [
            'findBy' => [$entity]
        ]);
        $doctrine = $this->createDoctrine($objectRepository);
        $this->executeProcessor($doctrine, null, new Filesystem());
        $finder = $this->createTestDirFinder();
        $this->assertCount(1, $finder);

    }


}