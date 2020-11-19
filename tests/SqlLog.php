<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Doctrine\DBAL\Logging\EchoSQLLogger;

/**
 * @author mati.andreas@ambientia.ee
 */
class SqlLog extends EchoSQLLogger
{
    public $log = [];

    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        $this->log[] = $sql;

    }

    public function reset(): void
    {
        $this->log = [];
    }

}
