<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;


/**
 *
 * from Symfony\Bridge\Doctrine\Test\DoctrineTestHelper
 */
class DoctrineTestHelper
{

    public static function createTestEntityManager(): EntityManager
    {
        if (!\extension_loaded('pdo_sqlite')) {
            TestCase::markTestSkipped('Extension pdo_sqlite is required.');
        }

        $config = self::createTestConfiguration();


        $params = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        return EntityManager::create($params, $config);
    }

    private static function createTestConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setEntityNamespaces(['AmbientiaQueueCommandTests' => 'Ambientia\QueueCommand']);
        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('SymfonyTests\Doctrine');
        $config->setMetadataDriverImpl(new MappingDriver());
        $config->setQueryCacheImpl(new ArrayCache());
        $config->setMetadataCacheImpl(new ArrayCache());

        return $config;
    }

}
