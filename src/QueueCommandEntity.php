<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use DateTime;


/**
 * @author mati.andreas@ambientia.ee
 * @internal use Repository to add and manage queue
 */
class QueueCommandEntity
{

    /**
     * @var int
     */
    private $id;

    /**
     * Service container id
     * Service must be public for usage
     *
     * @var string
     */
    private $service;

    /**
     * array of command arguments
     *
     * @var array
     */
    private $arguments = [];

    /**
     * Offset executing time
     * if not set, command will be executed as soon as possible
     *
     * @var DateTime|null
     */
    private $ttl;

    /**
     * command status
     *
     * @var string|null
     */
    private $status;

    /**
     * @var DateTime|null
     * @internal
     */
    private $started;

    /**
     * @var DateTime|null
     * @internal
     */
    private $ended;

    /**
     * Messages related to executing command
     * Set also if anything is returned by command
     *
     * @var string|null
     */
    private $message;

    /**
     * Execute order
     *
     * @var int
     */
    private $priority = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setService(string $service): void
    {
        $this->service = $service;
    }

    public function getService(): string
    {
        return $this->service;
    }

    /*
     * @param array $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setTtl(DateTime $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getTtl(): ?DateTime
    {
        return $this->ttl;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param DateTime $started
     *
     * @internal
     */
    public function setStarted(DateTime $started): void
    {
        $this->started = $started;
    }

    /**
     * @return DateTime|null
     * @internal
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * @param DateTime $ended
     *
     * @internal
     */
    public function setEnded(DateTime $ended): void
    {
        $this->ended = $ended;
    }

    /**
     * @return DateTime|null
     * @internal
     */
    public function getEnded()
    {
        return $this->ended;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

}
