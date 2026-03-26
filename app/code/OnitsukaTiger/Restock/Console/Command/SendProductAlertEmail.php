<?php

namespace OnitsukaTiger\Restock\Console\Command;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\ProductAlert\Model\Mailing\AlertProcessor;
use Magento\Framework\App\ResourceConnection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Magento\Catalog\Api\ProductRepositoryInterface;

class SendProductAlertEmail extends Command
{
    private const STORE_ID = 'storeId';
    /**
     * @var State
     */
    protected State $state;

    /**
     * @var Magento\ProductAlert\Model\Mailing\AlertProcessor
     */
    protected $alertProcessor;

    /**
     * @var Magento\Framework\App\ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param State $state
     * @param Magento\ProductAlert\Model\Mailing\AlertProcessor $alertProcessor
     * @param ResourceConnection $resourceConnection
     * @param string $name
     */
    public function __construct(
        State $state,
        AlertProcessor $alertProcessor,
        ResourceConnection $resourceConnection,
        ProductRepositoryInterface $productRepository,
        string $name = null
    ) {
        $this->state = $state;
        $this->alertProcessor = $alertProcessor;
        $this->resourceConnection = $resourceConnection;
        $this->productRepository = $productRepository;
        parent::__construct($name);
    }

    /**
     * Confiugre console command
     */
    public function configure()
    {
        $options = [
            new InputOption(
                self::STORE_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Store Id'
            )
        ];

        $this->setName('send:product:alert:email')
            ->setDescription('Send product alert email once back in stock');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * Process product stock alerts emails
     *
     * @param string $input
     * @param array $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $tableName = $this->resourceConnection->getTableName('product_alert_stock');
        //Initiate Connection
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['c' => $tableName],
                ['*']
            )->where(
                'c.status = ?',
                0
            );
        if ($storeId = $input->getOption(self::STORE_ID)) {
            $select->where(
                'c.store_id = ?',
                $storeId
            );
        } else {
            $select->where(
                'c.store_id != ?',
                5
            );
        }
        $select->where(
            'c.alert_type = ?',
            1
        );

        $restockCollection = $connection->fetchAll($select);

        $restockArr = [];
        $websiteIds = [];

        if (!empty($restockCollection)) {
            foreach ($restockCollection as $restock) {
                $websiteIds[] = $restock['website_id'];
                $productId = $restock['product_id'];
                $product = $this->productRepository->getById($productId, false, $restock['website_id']);
                if (in_array($restock['website_id'], $websiteIds) && $product->getStatus() == "1") {
                    $restockArr[$restock['website_id']][$restock['product_id']] = $restock['customer_id'];
                }
            }

            foreach ($restockArr as $key => $restockValue) {
                $restockData = array_unique($restockValue);
                $this->alertProcessor->process('stock', array_unique($restockValue), $key);
            }
            $output->writeln('Stock alert email has been processed successfully.');
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } else {
            $output->writeln('No product to send Restock email');
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }
    }
}
