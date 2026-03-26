<?php

namespace OnitsukaTiger\Catalog\Cron;

use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory as BestSellersCollectionFactory;
use OnitsukaTiger\Logger\Logger;

class UpdateBestsellersProduct
{
    public const DEFAULT_BEST_PERIOD = 60;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resourceConnection;

    /**
     * @var BestSellersCollectionFactory
     */
    protected BestSellersCollectionFactory $bestsellersCollection;

    /**
     * @var ProductRepository
     */
    protected ProductRepository $productRepository;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $productCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param ResourceConnection $resourceConnection
     * @param BestSellersCollectionFactory $bestsellersCollection
     * @param ProductRepository $productRepository
     * @param CollectionFactory $productCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        BestSellersCollectionFactory $bestsellersCollection,
        ProductRepository $productRepository,
        CollectionFactory $productCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        Logger               $logger,
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->bestsellersCollection = $bestsellersCollection;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $range =  $this->scopeConfig->getValue('amsorting/bestsellers/best_period') ?? self::DEFAULT_BEST_PERIOD;
        $time = '-' . $range . ' days';
        $fromDate = date('Y-m-d', strtotime($time));
        $toDate = date('Y-m-d');
        $connection = $this->resourceConnection->getConnection();
        $select = "SELECT * FROM sales_bestsellers_aggregated_daily WHERE (period >= '$fromDate') AND (period <= '$toDate')";
        $bestsellersCollection = $connection->fetchAll($select);
        $table = $connection->getTableName('bestsellers_product_list');
        $connection->truncateTable($table);
        $data = [];

        try {
            $connection->beginTransaction();
            foreach ($bestsellersCollection as $bestsellersProduct) {
                if (!$bestsellersProduct['product_id'] || !$bestsellersProduct['store_id']) {
                    continue;
                }

                $product = $this->productCollectionFactory->create()
                    ->addAttributeToSelect('material_code')
                    ->addStoreFilter($bestsellersProduct['store_id'])
                    ->addFieldToFilter('entity_id', $bestsellersProduct['product_id'])
                    ->getItems();

                if ($product && $product[$bestsellersProduct['product_id']]->getMaterialCode()) {
                    $data[] = [
                        'period' => $bestsellersProduct['period'] ?? null,
                        'store_id' => $bestsellersProduct['store_id'],
                        'product_id' => $bestsellersProduct['product_id'],
                        'product_name' => $bestsellersProduct['product_name'] ?? null,
                        'product_price' => $bestsellersProduct['product_price'],
                        'qty_ordered' => $bestsellersProduct['qty_ordered'],
                        'rating_pos' => $bestsellersProduct['rating_pos'],
                        'material_code' => $product[$bestsellersProduct['product_id']]->getMaterialCode(),
                    ];
                }
            }
            if ($data) {
                $connection->insertMultiple($table, $data);
            }
            $connection->commit();
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Insert table bestsellers_product_list error %s', $e->getMessage()));
            $connection->rollBack();
        }
    }
}
