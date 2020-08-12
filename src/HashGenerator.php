<?php

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

/**
 * @author mati.andreas@ambientia.ee
 */
class HashGenerator
{
    public function generate(string $service, array $arguments): string
    {
        return hash(
            'sha256',
            print_r([
                'service' => $service,
                'arguments' => $arguments,
            ], true)
        );
    }
}