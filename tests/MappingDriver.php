<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\QueueCommandEntity;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * @author mati.andreas@ambientia.ee
 */
class MappingDriver implements \Doctrine\Persistence\Mapping\Driver\MappingDriver
{

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $class = $metadata->getReflectionClass();

        $metadata->setPrimaryTable([
            'name' => 'queue_command',
        ]);

        foreach ($class->getProperties() as $property) {


            $mapping = [
                'fieldName' => $property->getName(),
            ];

            switch ($property->getName()) {

                case 'id':
                    $mapping['id'] = true;
                    $mapping['type'] = 'integer';
                    $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_AUTO);
                    break;

                case 'service':
                    $mapping['length'] = 100;
                    break;

                case 'arguments':
                    $mapping['type'] = 'array';
                    break;

                case 'hash':
                    $mapping['length'] = 64;
                    break;

                case 'status':
                    $mapping['nullable'] = true;
                    break;

                case 'ttl':
                    $mapping['type'] = 'datetime';
                    $mapping['nullable'] = true;
                    break;
                case 'priority':
                    $mapping['type'] = 'smallint';
                    break;

                default:
                    // only these fields used by query builder in repository
                    continue(2);

            }


            $metadata->mapField($mapping);


        }
    }

    public function getAllClassNames()
    {
        return [
            QueueCommandEntity::class,
        ];

    }

    public function isTransient($className)
    {

    }
}