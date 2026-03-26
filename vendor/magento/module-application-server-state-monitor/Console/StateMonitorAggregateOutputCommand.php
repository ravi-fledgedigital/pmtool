<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\Console;

use Magento\ApplicationServerStateMonitor\StateMonitor\AggregateOutput;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Aggregates Output files for StateMonitor
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StateMonitorAggregateOutputCommand extends Command
{
    /**
     * Aggregate Output constructor
     *
     * @param AggregateOutput $aggregateOutput
     */
    public function __construct(private readonly AggregateOutput $aggregateOutput)
    {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('server:state-monitor:aggregate-output')
            ->setDescription('Aggregate output from state monitor of ApplicationServer')
            ->setDefinition($this->getOptionsList());
    }

    /**
     * Get list of options
     *
     * @return array
     */
    private function getOptionsList(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $aggregateOutputOutput = $this->aggregateOutput->execute();
        $output->write($aggregateOutputOutput);
        return Cli::RETURN_SUCCESS;
    }
}
