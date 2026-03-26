<?php

namespace OnitsukaTigerCpss\Crm\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SftpReadFile extends Command
{
    const STORE_CODE = 'storeCode';

    /**
     * @var \Cpss\Pos\Cron\ShopInfo
     */
    protected $cpssShopinfo;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @param \Cpss\Pos\Cron\ShopInfo $cpssTransferFiles
     * @param \Magento\Framework\App\State $appState
     * @param string|null $name
     */
    public function __construct(
        \Cpss\Crm\Cron\ShopInfo $cpssShopinfo,
        \Magento\Framework\App\State $appState,
        private \OnitsukaTigerCpss\Crm\Helper\HelperData $helperData,
        string $name = null
    ) {
        parent::__construct($name);
        $this->cpssShopinfo = $cpssShopinfo;
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

        $this->setName('OT:cpss-sftp-file-download');
        $this->setDescription('Cpss SFTP File Download');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($storeCode = $input->getOption(self::STORE_CODE)) {
            $this->appState->setAreaCode('crontab');

            $storeIds = $this->helperData->getStoreIds();

            $storeId = (isset($storeIds[$storeCode])) ? $storeIds[$storeCode] : 1;

            $output->writeln("------- File download started -------");
            $this->cpssShopinfo->applyShopInfo($storeId);
            $output->writeln("------- File download ended -------");
            return Command::SUCCESS;
        } else {
            $output->writeln("Store Code is a required params");
            return Command::FAILURE;
        }
    }

}
