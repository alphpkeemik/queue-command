<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\DBAL\Logging\SQLLogger;

/**
 * @author mati.andreas@ambientia.ee
 */
class SqlLog implements SQLLogger
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

    public function stopQuery()
    {
    }
}
