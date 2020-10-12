# Queue command

PHP library for executing queued commands

## usage
### Adding to queue
```
<?php

use Ambientia\QueueCommand\QueueCommandEntity;
use App\MyModule\MyService;
use Doctrine\Common\Persistence\ObjectManager;


$queueCommand = new QueueCommandEntity();
$queueCommand->setService(MyService::class);

/** @var ObjectManager $entityManager */
$entityManager->persist($queueCommand);
$entityManager->flush();
```

### Creating queue command
```
<?php
namespace App\MyModule;

class MyService
{
    public function execute(int $arg1)
    {

        //do some stuff
        $result = $arg1 + 1;

        // return some message if needed
        return "$arg1 processed ro $result";

    }
}
```

### Add cron
` * * * * * ambientia:queue-command:execute >> /path/to/log/file 2>&1`

### add data cleaner
https://github.com/alphpkeemik/data-cleaner

## development
### todo
* remove container dependency
* todo replace merge it with find in EntityProcessor
* todo add waiting / persistent running
* add data cleaner interface

### Running code fixer

Run php cs fixer `./vendor/bin/php-cs-fixer fix`

### Running the tests

Run tests with phpunit `./vendor/bin/phpunit`

### Running analyzer

Run phan `./vendor/bin/phan`