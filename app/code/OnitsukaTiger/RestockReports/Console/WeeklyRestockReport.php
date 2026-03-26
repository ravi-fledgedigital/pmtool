<?php

namespace OnitsukaTiger\RestockReports\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WeeklyRestockReport extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var
     */
    protected $output;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param \OnitsukaTiger\RestockReports\Model\RestockDataFactory $restockDataFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \OnitsukaTiger\RestockReports\Model\RestockReportFactory $restockReportFactory
     * @param string|null $name
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        private \OnitsukaTiger\RestockReports\Model\RestockDataFactory $restockDataFactory,
        private \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        private \OnitsukaTiger\RestockReports\Model\RestockReportFactory $restockReportFactory,
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
        $this->setName('OT:weekly-restock-report');
        $this->setDescription('Weekly Restock Report');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->appState->setAreaCode('crontab');

        $this->output->writeln("------- Weekly Restock Report Started -------");
        $this->generateRestockReport($this->output);
        $this->output->writeln("------- Weekly Restock Report Ended -------");

        return Command::SUCCESS;
    }

    /**
     * Generate restock report.
     *
     * @param $output
     * @return void
     */
    private function generateRestockReport($output)
    {

        try {
            $fromDate = date('Y-m-d', strtotime("-1 week"));
            $toDate = date('Y-m-d');
            $rowData = $this->restockReportFactory->create();
            $date = $this->timezoneInterface->date()->format("y-m-d");
            $rowData->setData("from_date", $fromDate);
            $rowData->setData("to_date", $toDate);
            $queue = "restockdata-weekly-report" . $fromDate . "-to-" . $toDate;
            $rowData->setData("created_at", $date);
            $rowData->setData("name", $queue);

            $rowData->save();
            if ($rowData->getId()) {
                $restockData = $this->restockDataFactory->create()->process($rowData->getId());
            }
            $output->writeln("Success Message: Weekly Restock Report Generated. Filename: " . $queue);
        } catch (\Exception $e) {
            $output->writeln("Error Message: " . $e->getMessage());
        }
    }
}
