<?php
namespace OnitsukaTiger\Command\Console\Tools;

use Symfony\Component\Console\Command\Command;

/**
 * Class NetSuiteOrder
 */
class NetSuiteOrder extends \OnitsukaTiger\Command\Console\Command
{
    const OPTION_ID = 'id';

    /**
     * @var \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchOrder
     */
    protected $searchOrder;

    /**
     * @param \OnitsukaTiger\Logger\Logger $logger
     * @param \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchOrder $searchOrder
     */
    public function __construct(
        \OnitsukaTiger\Logger\Logger $logger,
        \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchOrder $searchOrder
    ) {
        $this->searchOrder = $searchOrder;

        parent::__construct($logger);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $options = [
            new \Symfony\Component\Console\Input\InputOption(
                self::OPTION_ID,
                'i',
                \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED | \Symfony\Component\Console\Input\InputOption::VALUE_IS_ARRAY,
                'External ID'
            )
        ];

        $this->setName('tools:netsuite:order');
        $this->setDescription('to show netsuite order');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $arr = $input->getOption(self::OPTION_ID);
        foreach($arr as $id) {
            echo sprintf("---%s---", $id) . PHP_EOL;
            $result = $this->searchOrder->searchByExternalId($id);
            echo json_encode($result, JSON_PRETTY_PRINT);
            echo sprintf("---%s---", $id) . PHP_EOL;
        }

        return Command::SUCCESS;
    }
}
