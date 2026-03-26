<?php

namespace OnitsukaTiger\AEPNewsletterFileExport\Model\ResourceModel;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Newsletter subscriber resource model
 */
class Subscriber extends AbstractDb
{
    /**
     * DB connection
     *
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param string $connectionName
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        Context $context,
        $connectionName = null,
        StoreManagerInterface $storeManager = null
    ) {
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model. Get tablename from config
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('newsletter_subscriber_backup', 'subscriber_id');
    }

    /**
     * Load by customer id
     *
     * @param int $customerId
     * @param int $websiteId
     * @return array
     * @since 100.4.0
     */
    public function loadByCustomerId(int $customerId, int $websiteId): array
    {
        $storeIds = $this->storeManager->getWebsite($websiteId)->getStoreIds();
        $select = $this->connection->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId)
            ->where('store_id IN (?)', $storeIds)
            ->limit(1);

        $data = $this->connection->fetchRow($select);
        if (!$data) {
            return [];
        }

        return $data;
    }
}
