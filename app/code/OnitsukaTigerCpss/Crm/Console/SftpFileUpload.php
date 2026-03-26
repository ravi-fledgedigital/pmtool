<?php

namespace OnitsukaTigerCpss\Crm\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SftpFileUpload extends Command
{
    const STORE_CODE = 'store-code';

    /**
     * @var \Cpss\Pos\Cron\CpssTransferFiles
     */
    protected $cpssTransferFiles;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @param \Cpss\Pos\Cron\CpssTransferFiles $cpssTransferFiles
     * @param \Magento\Framework\App\State $appState
     * @param string|null $name
     */
    public function __construct(
        \Cpss\Pos\Cron\CpssTransferFiles $cpssTransferFiles,
        \Magento\Framework\App\State $appState,
        string $name = null
    ) {
        parent::__construct($name);
        $this->cpssTransferFiles = $cpssTransferFiles;
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

        $this->setName('OT:cpss-sftp-file-transfer');
        $this->setDescription('Cpss SFTP File Transfer');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($storeCode = $input->getOption(self::STORE_CODE)) {
            $this->appState->setAreaCode('crontab');

            $output->writeln("------- File upload started -------");
            $this->cpssTransferFiles->executeEc($storeCode);
            $output->writeln("------- File upload ended -------");

            return Command::SUCCESS;
        } else {
            $output->writeln("Store Code is a required params");
            return Command::FAILURE;
        }

    }

}
