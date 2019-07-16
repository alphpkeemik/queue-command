<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand;

use Doctrine\Common\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @author mati.andreas@ambientia.ee
 */
class FlockStoreCleaner
{

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ManagerRegistry $doctrine, Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    public function process(string $lockPath): void
    {

        $em = $this->doctrine->getManagerForClass(QueueCommandEntity::class);
        $repo = $em->getRepository(QueueCommandEntity::class);
        $criteria = ['status' => States::PROCESSING];

        $executing = $repo->findBy($criteria, ['started' => 'ASC'], 0, 1);
        $time = null;
        if ($executing) {
            $time = $executing[0]->getStarted()->getTimestamp();
        }

        $finder = new Finder();
        $finder->in($lockPath);
        $finder->name('sf.*.*.lock');
        $finder->files();

        foreach ($finder as $file) {
            $this->logger->debug('removing abandoned lock file', [
                'file' => $file->getFilename(),
                'path' => $lockPath
            ]);

            if ($time and $file->getMTime() >= $time) {
                continue;
            }
            $this->filesystem->remove($file->getRealPath());
        }
    }
}