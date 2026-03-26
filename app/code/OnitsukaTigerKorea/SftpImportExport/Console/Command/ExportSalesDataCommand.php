<?php

namespace OnitsukaTigerKorea\SftpImportExport\Console\Command;

use Magento\Framework\App\State;
use OnitsukaTiger\Command\Console\Command;
use OnitsukaTiger\Logger\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export\SalesData;

class ExportSalesDataCommand extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var LoggerInterface|Logger
     */
    protected $logger;

    /**
     * @var SalesData
     */
    protected $exportSalesData;


    /**
     * ExportSalesDataCommand constructor.
     * @param Logger $logger
     * @param State $state
     * @param SalesData $exportSalesData
     */
    public function __construct(
        Logger $logger,
        State $state,
        SalesData $exportSalesData
    )
    {
        $this->state = $state;
        $this->exportSalesData = $exportSalesData;
        $this->logger = $logger;
        parent::__construct($logger, 'sftp:export:salesdata');
    }


    protected function configure()
    {
        $this->setName('sftp:export:salesdata')
            ->setDescription('SFTP Export Sales Data');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $timeStart = time();
        $result = $this->exportSalesData->execute();
        $timeFinish = time();

        $totalTime = $timeFinish - $timeStart;
        $timeLog = 'Export successfully in ' . $totalTime .' seconds.';

        $output->writeln('<info>' . $result . '</info>');
        $output->writeln('<info>' . $timeLog . '</info>');

        return Command::SUCCESS;
    }
}
