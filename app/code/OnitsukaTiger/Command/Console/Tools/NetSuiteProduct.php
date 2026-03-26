<?php
namespace OnitsukaTiger\Command\Console\Tools;

use Magento\Checkout\Exception;
use Symfony\Component\Console\Command\Command;

/**
 * Class CsvExport
 */
class NetSuiteProduct extends \OnitsukaTiger\Command\Console\Command
{
    const OPTION_SKU = 'sku';

    /**
     * @var \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchProduct
     */
    protected $searchProduct;

    /**
     * @param \OnitsukaTiger\Logger\Logger $logger
     * @param \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchProduct $searchProduct
     */
    public function __construct(
        \OnitsukaTiger\Logger\Logger $logger,
        \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchProduct $searchProduct
    ) {
        $this->searchProduct = $searchProduct;

        parent::__construct($logger);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $options = [
            new \Symfony\Component\Console\Input\InputOption(
                self::OPTION_SKU,
                's',
                \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED | \Symfony\Component\Console\Input\InputOption::VALUE_IS_ARRAY,
                'SKU'
            )
        ];

        $this->setName('tools:netsuite:product');
        $this->setDescription('to show netsuite product');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return null|int
     * @throws Exception
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $errors = [];
        $arr = $input->getOption(self::OPTION_SKU);
        foreach($arr as $sku) {
            try {
                echo sprintf("---%s---", $sku) . PHP_EOL;
                $result = $this->searchProduct->searchBySku($sku);
                echo json_encode($result, JSON_PRETTY_PRINT);
                echo sprintf("---%s---", $sku) . PHP_EOL;
            } catch (\Throwable $e) {
                $errors[] = [
                    'sku' => $sku,
                    'msg' => $e->getMessage()
                ];
            }
        }
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $output->writeln(sprintf("Error Sku %s : %s", $error['sku'], $error['msg'])) ;
            }
        }

        return Command::SUCCESS;
    }
}
