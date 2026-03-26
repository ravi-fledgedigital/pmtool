<?php
//phpcs:ignoreFile
namespace Cpss\Pos\Model;

class PosData extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'cpss_pos_pos_data';

    /**
     * Model cache tag for clear cache in after save and after delete
     *
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'pos_data';

    protected $shopReceipt;

    protected $shopReceiptFactory;

    protected $shopReceiptCollection;

    protected $logger;

    protected $posCollectionFactory;

    protected $resourceConnection;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Cpss\Crm\Model\ShopReceipt $shopReceipt,
        \Cpss\Crm\Model\ShopReceiptFactory $shopReceiptFactory,
        \Cpss\Crm\Model\ResourceModel\ShopReceipt\CollectionFactory $shopReceiptCollection,
        \Cpss\Pos\Logger\Logger $logger,
        \Cpss\Pos\Model\ResourceModel\PosData\CollectionFactory $posCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->shopReceipt = $shopReceipt;
        $this->shopReceiptFactory = $shopReceiptFactory;
        $this->shopReceiptCollection = $shopReceiptCollection;
        $this->logger = $logger;
        $this->posCollectionFactory = $posCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function __destruct()
    {
        if ($this->resourceConnection) {
            $this->resourceConnection->closeConnection();
        }
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Cpss\Pos\Model\ResourceModel\PosData');
    }

    /**
     * Return a unique id for the model.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function loadAllShopItemsByPurchaseId($purchaseId, $columns = null)
    {
        $shopItems = $this->posCollectionFactory->create();
        if ($columns != null) {
            $shopItems->addFieldToSelect($columns);
        }
        $shopItems->addFieldToFilter("purchase_id", ["in" => $purchaseId]);
        $shopItems->addFieldToFilter('transaction_type', ['neq' => 3]);
        return $shopItems;
    }

    public function loadAllShopDataForCpssCsv($date, $request = "addpoint", $storeCode = 'sg')
    {
        $cpssData = $this->shopReceiptCollection->create()
            ->addFieldToSelect(['shop_id', 'purchase_id'])
            /*->addFieldToFilter("main_table.created_at", ["lteq" => $date])*/
            ->addFieldToFilter("member_id", ["notnull" => true])
            /*->addFieldToFilter("total_amount", ["gt" => 0])*/
            ->addFieldToFilter("store_code", ["eq" => $storeCode]);
        if ($request == "addpoint") {
            $cpssData->addFieldToFilter("add_point_request_date", ["null" => true]);
        } else {
            $cpssData->addFieldToFilter("return_purchase_id", ["notnull" => true])
                ->addFieldToFilter("get_back_point_request_date", ["null" => true]);
        }

        $cpssData->addFieldToFilter("child_table.purchase_id", ["notnull" => true]);
        $cpssData->getSelect()->joinLeft(
            ["child_table" => "sales_real_store_order_item"],
            "main_table.purchase_id = child_table.purchase_id",
            []
        )->group("purchase_id");

        $this->logger->info($cpssData->getSelect()->__toString());

        if (empty($cpssData)) {
            return [];
        }

        $data = [];
        foreach ($cpssData as $cpss) {
            if (isset($data[$cpss["shop_id"]])) {
                $data[$cpss["shop_id"]]["purchase_id"] = $data[$cpss["shop_id"]]["purchase_id"] . ',' . $cpss["purchase_id"];
            } else {
                $data[$cpss["shop_id"]]["purchase_id"] = $cpss["purchase_id"];
            }
        }

        return $data;
    }

    public function loadAllShopDataForCpssCsvRecovery($purchaseIds, $request = "addpoint")
    {
        $cpssData = $this->shopReceiptCollection->create()
            ->addFieldToSelect(['shop_id', 'purchase_id'])
            ->addFieldToFilter('main_table.purchase_id', ['in' => $purchaseIds])
            ->addFieldToFilter("member_id", ["notnull" => true]);
        if ($request == "addpoint") {
            $cpssData->addFieldToFilter("add_point_request_date", ["null" => true]);
        } else {
            $cpssData->addFieldToFilter("return_purchase_id", ["notnull" => true])
                ->addFieldToFilter("get_back_point_request_date", ["null" => true]);
        }

        $cpssData->addFieldToFilter("child_table.purchase_id", ["notnull" => true]);
        $cpssData->getSelect()->joinLeft(
            ["child_table" => "sales_real_store_order_item"],
            "main_table.purchase_id = child_table.purchase_id",
            []
        )->group("purchase_id");

        $this->logger->info("Recovery: " . $cpssData->getSelect()->__toString());

        if (empty($cpssData)) {
            return [];
        }

        $data = [];
        foreach ($cpssData as $cpss) {
            if (isset($data[$cpss["shop_id"]])) {
                $data[$cpss["shop_id"]]["purchase_id"] = $data[$cpss["shop_id"]]["purchase_id"] . ',' . $cpss["purchase_id"];
            } else {
                $data[$cpss["shop_id"]]["purchase_id"] = $cpss["purchase_id"];
            }
        }

        return $data;
    }

    public function loadShopByReturnPurchaseId($originPurchaseId)
    {
        return $this->shopReceipt->loadByAttribute("return_purchase_id", $originPurchaseId);
    }

    public function loadShopByExchPurchaseId($originPurchaseId)
    {
        return $this->shopReceipt->loadByAttribute("exch_purchase_id", $originPurchaseId);
    }

    public function loadShopByPurchaseId($purchaseId)
    {
        return $this->shopReceiptFactory->create()->loadByAttribute("purchase_id", $purchaseId);
    }

    public function loadShopByMultiplePurchaseId($multiplePurchaseId, $columns = [])
    {
        $collection = $this->shopReceiptCollection->create();
        if (!empty($columns)) {
            $collection->addFieldToSelect($columns);
        }
        $collection->addFieldToFilter("purchase_id", ["in" => $multiplePurchaseId])
            ->setOrder("purchase_id", "ASC");

        return $collection;
    }

    public function updateRequestDate($updatedData)
    {
        try {
            $tableName = $this->resourceConnection->getTableName("sales_real_store_order");
            $connection = $this->resourceConnection->getConnection();
            $data = [];
            // $col = "add_point_request_date";
            // if ($column != "add_point") {
            //     $col = "get_back_point_request_date";
            // }
            // foreach ($realStoreIds as $id) {
            //     $data[] = [
            //         "entity_id" => $id,
            //         $col => $date
            //     ];
            // }

            foreach ($updatedData as $k => $updates) {
                $data[] = $updates;
            }

            $connection->beginTransaction();
            $connection->insertOnDuplicate($tableName, $data);
            $connection->commit();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
