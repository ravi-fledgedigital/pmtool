<?php

namespace OnitsukaTiger\NetSuite\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncOrderStatusDevliverToNetsuite extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    public function __construct(
        \Magento\Framework\App\State $appState,
        protected \OnitsukaTiger\NetSuite\Model\SuiteTalk\Delivered $deliveredOrder,
        string $name = null
    ) {
        parent::__construct($name);
        $this->appState = $appState;
    }

    /**
     * Method configure
     */
    protected function configure()
    {
        $this->setName('OT:sync-order-status-delivered-to-netsuite');
        $this->setDescription('Sync order status delivered to netsuite');
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->appState->setAreaCode('crontab');

        $this->output->writeln("------- Order status sync start -------");
        $this->deliveredOrder->execute();
        $this->output->writeln("------- Order status sync start -------");
    }
}
