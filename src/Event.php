<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand;

/**
 * @author mati.andreas@ambientia.ee
 */
class Event
{
    /**
     * @var QueueCommandEntity
     */
    private $QueueCommand;

    /**
     * @var string
     */
    private $state;

    public function __construct(QueueCommandEntity $QueueCommand, string $state)
    {
        $this->QueueCommand = $QueueCommand;
        $this->state = $state;
    }

    public function getQueueCommand(): QueueCommandEntity
    {
        return $this->QueueCommand;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
