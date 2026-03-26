<?php

namespace OnitsukaTigerCpss\Pos\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePosdata extends Command
{
    const STORE_CODE = 'storeCode';

    /**
     * @var \Cpss\Pos\Cron\UpdatePosdata
     */
    protected $cpssUpdatePosdata;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @param \Cpss\Pos\Cron\UpdatePosdata $cpssUpdatePosdata
     * @param \Magento\Framework\App\State $appState
     * @param string|null $name
     */
    public function __construct(
        \Cpss\Pos\Cron\UpdatePosdata $cpssUpdatePosdata,
        \Magento\Framework\App\State $appState,
        string $name = null
    ) {
        parent::__construct($name);
        $this->cpssUpdatePosdata = $cpssUpdatePosdata;
        $this->appState = $appState;
    }

    /**
     * Method configure
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::STORE_CODE,
                null,
                InputOption::VALUE_REQUIRED,
                'Store Code'
            )
        ];

        $this->setName('OT:cpss-update-pos-data');
        $this->setDescription('Cpss Update Pos Data');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($storeCode = $input->getOption(self::STORE_CODE)) {
            $this->appState->setAreaCode('crontab');

            $output->writeln("------- File upload started -------");
            $this->cpssUpdatePosdata->execute($storeCode);
            $output->writeln("------- File upload ended -------");
            return Command::SUCCESS;
        } else {
            $output->writeln("Store code is a required parameter.");
            return Command::FAILURE;
        }
    }

}
