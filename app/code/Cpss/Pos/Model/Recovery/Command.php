<?php
namespace Cpss\Pos\Model\Recovery;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class Command extends \Symfony\Component\Console\Command\Command
{
    const SEND_CSV = 'send_csv';

    protected $process;

    public function __construct(
        \Cpss\Pos\Cron\CronSchedRunRecovery $process,
        string $name = null
    ) {
        $this->process = $process;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('execute:pos_recovery')
             ->setDescription('POS Data Recovery');
        
        $this->addOption(
                self::SEND_CSV,
                null,
                InputOption::VALUE_REQUIRED,
                'Send CSV'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // sample command
        // php bin/magento execute:pos_recovery --send_csv=1
        $send = $input->getOption(self::SEND_CSV);
        $this->process->execute($send);
    }
}