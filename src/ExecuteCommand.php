<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author mati.andreas@ambientia.ee
 */
class ExecuteCommand extends Command
{

    /**
     * @var CrashedProcessor
     */
    private $crashedProcessor;

    /**
     * @var FlockStoreCleaner
     */
    private $flockStoreCleaner;

    /**
     * @var QueueProcessor
     */
    private $queueProcessor;
    /**
     * @var CriteriaBuilderInterface
     */
    private $criteriaBuilder;

    public function setCrashedProcessor(CrashedProcessor $crashedProcessor): void
    {
        $this->crashedProcessor = $crashedProcessor;
    }

    public function setFlockStoreCleaner(FlockStoreCleaner $flockStoreCleaner): void
    {
        $this->flockStoreCleaner = $flockStoreCleaner;
    }

    public function setQueueProcessor(QueueProcessor $queueProcessor): void
    {
        $this->queueProcessor = $queueProcessor;
    }

    public function setCriteriaBuilder(CriteriaBuilderInterface $criteriaBuilder): void
    {
        $this->criteriaBuilder = $criteriaBuilder;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('ambientia:queue-command:execute')
            ->setDescription('Process command queue')
            ->addOption(
                'time-limit',
                null,
                InputOption::VALUE_REQUIRED,
                'The time limit for processing commands (in seconds).',
                60
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timeLimit = $input->getOption('time-limit');
        $this->crashedProcessor->process();
        $this->flockStoreCleaner->process();
        $criteria = $this->criteriaBuilder->build();
        $this->queueProcessor->process($criteria, $timeLimit);
        return 0;
    }
}