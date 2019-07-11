<?php

declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand;

/**
 * @author mati.andreas@ambientia.ee
 */
class Events
{
    const EXECUTE_STARTED = 'queue-command.execute.started';
    const EXECUTE_FINISHED = 'queue-command.execute.finished';
    const EXECUTE_FATALED = 'queue-command.execute.fataled';
    const EXECUTE_FAILED = 'queue-command.execute.failed';
}
