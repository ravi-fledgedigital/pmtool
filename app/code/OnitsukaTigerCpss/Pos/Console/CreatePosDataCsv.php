<?php

namespace OnitsukaTigerCpss\Pos\Console;

use Cpss\Pos\Helper\CreateCsv;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreatePosDataCsv extends Command
{
    const STORE_CODE = 'storeCode';

    /**
     * @var CreateCsv
     */
    protected $createCsv;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @param CreateCsv $createCsv
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        CreateCsv $createCsv,
        State $appState,
        string $name = null
    ) {
        parent::__construct($name);
        $this->createCsv = $createCsv;
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

        $this->setName('OT:cpss-pos-data-csv-file-creation');
        $this->setDescription('Cpss POS Data CSV File Creation');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($storeCode = $input->getOption(self::STORE_CODE)) {
            $this->appState->setAreaCode('crontab');

            $output->writeln("------- File creation started -------");
            $this->createCsv->generateRealStoresData(false, [], $storeCode);
            $output->writeln("------- File creation ended -------");
            return Command::SUCCESS;
        } else {
            $output->writeln("Store code is a required parameter.");
            return Command::FAILURE;
        }
    }

}
