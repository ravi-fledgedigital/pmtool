<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs\ResourceModel;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Model\Source\Status;
use Magento\Framework\DB\Helper;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\Store;

class Collection extends AbstractCollection
{
    use CollectionTrait;

    public const MULTI_STORE_FIELDS_MAP = [
        'tab_title' => 'IFNULL(noDefaultStore.tab_title, store.tab_title)',
        'content' => 'IFNULL(noDefaultStore.content, store.content)',
        'status' => 'IFNULL(noDefaultStore.status, store.status)',
    ];

    /**
     * @var array
     */
    protected $_map = [
        'fields' => [
            'tab_title' => 'noDefaultStore.tab_title',
            'status' => 'noDefaultStore.status',
            'content' => 'noDefaultStore.content'
        ]
    ];

    /**
     * @var Helper
     */
    protected $dbHelper;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    private $sessionFactory;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Helper $dbHelper,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        ?\Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        ?\Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->dbHelper = $dbHelper;
        $this->sessionFactory = $sessionFactory;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            \Amasty\CustomTabs\Model\Tabs\Tabs::class,
            \Amasty\CustomTabs\Model\Tabs\ResourceModel\Tabs::class
        );
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
        $this->_map['fields']['tab_id'] = 'main_table.tab_id';
    }

    /**
     * Try to get mapped field name for filter to collection
     *
     * @param   string $field
     * @return  string
     */
    protected function _getMappedField($field)
    {
        $mapper = $this->_getMapper();

        //fix fatal with zend expression
        if (is_string($field) && isset($mapper['fields'][$field])) {
            $mappedField = $mapper['fields'][$field];
        } else {
            $mappedField = $field;
        }

        return $mappedField;
    }

    /**
     * @return array
     */
    public function getExistingTabs()
    {
        $this->getSelect()->reset(Select::COLUMNS)
            ->columns([TabsInterface::NAME_IN_LAYOUT]);

        $tabs = $this->getConnection()->fetchCol($this->getSelect());

        return $tabs;
    }

    /**
     * @param array $types
     * @param array $tabIds
     * @param int $storeId
     * @return $this
     */
    public function getCustomTabByParams(array $types, array $tabIds, int $storeId)
    {
        $this->addFieldToFilter(TabsInterface::TAB_TYPE, ['in' => $types]);
        $this->addStoreWithDefault($storeId);

        $tabIds[] = 0; //prevent fatal on empty array
        $this->getSelect()
            ->where(
                sprintf(
                    'IFNULL(noDefaultStore.%1$s, store.%1$s) = ?',
                    TabsInterface::STATUS
                ),
                Status::ENABLED
            )
            ->where(sprintf('IFNULL(noDefaultStore.%1$s, store.%1$s) <> ""', TabsInterface::CONTENT))
            ->where(sprintf('IFNULL(noDefaultStore.%1$s, store.%1$s) <> ""', TabsInterface::TAB_TITLE))
            ->where("CONCAT(',',customer_groups,',') like '%,?,%'", $this->getCurrentCustomerGroupId())
            ->where(
                sprintf(
                    '%s IS NULL OR %s IN(%s)',
                    TabsInterface::CONDITIONS_SERIALIZED,
                    'main_table.' . TabsInterface::TAB_ID,
                    implode(',', $tabIds)
                )
            );

        return $this;
    }

    /**
     * @return int
     */
    protected function getCurrentCustomerGroupId()
    {
        return (int)$this->getCustomerSession()->getCustomerGroupId() ? : 0;
    }

    /**
     * @return \Magento\Customer\Model\Session
     */
    private function getCustomerSession()
    {
        return $this->sessionFactory->create();
    }

    public function setLimit(?int $limit): void
    {
        $this->getSelect()->limit($limit);
    }

    public function getStoreIdsForEnabledTab(int $tabId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['content' => $this->getTable(Tabs::CONTENT_TABLE_NAME)],
            ['store_id']
        )
            ->where('content.tab_id = ?', $tabId)
            ->where('IFNULL(content.status, ?)', Status::ENABLED);
        $storeIds = array_map('intval', $connection->fetchCol($select));

        if ($storeIds === [Store::DEFAULT_STORE_ID]) {
            return $storeIds;
        }

        return array_filter($storeIds, function ($storeId) {
            return $storeId !== Store::DEFAULT_STORE_ID;
        });
    }
}
