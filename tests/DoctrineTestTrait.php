<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\QueueCommandEntity;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author mati.andreas@ambientia.ee
 */
trait DoctrineTestTrait
{
    private function createDoctrine(SqlLog $SQLLogger = null): ManagerRegistry
    {
        $em = DoctrineTestHelper::createTestEntityManager();
        $st = new SchemaTool($em);
        $st->updateSchema($em->getMetadataFactory()->getAllMetadata(), false);

        $mr = $this->createMock(ManagerRegistry::class);
        $mr
            ->method('getManagerForClass')
            ->with(QueueCommandEntity::class)
            ->willReturn($em);
        if ($SQLLogger) {
            $em->getConfiguration()->setSQLLogger($SQLLogger);
        }

        return $mr;
    }

}