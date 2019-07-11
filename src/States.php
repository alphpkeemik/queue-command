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
class States
{
    const PROCESSING = 'processing';
    const FAILED = 'failed';
    const FINISHED = 'finished';
    const FATALED = 'fataled';
}
