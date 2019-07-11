<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand;

use Symfony\Contracts\EventDispatcher\Event as Base;

/**
 * @author mati.andreas@ambientia.ee
 */
class Event extends Base
{

    /**
     * Queue command
     *
     * @var QueueCommandEntity
     */
    private $QueueCommand;

    public function __construct(QueueCommandEntity $QueueCommand)
    {
        $this->QueueCommand = $QueueCommand;
    }

    /**
     * get Queue command
     *
     * @return QueueCommandEntity
     */
    public function getQueueCommand(): QueueCommandEntity
    {
        return $this->QueueCommand;
    }
}
