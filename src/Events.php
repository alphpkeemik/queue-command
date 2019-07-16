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
    const EXECUTE_STARTED = 'started';
    const EXECUTE_FINISHED = 'finished';
    const EXECUTE_FATAL = 'fatal';
    const EXECUTE_FAILED = 'failed';
}
