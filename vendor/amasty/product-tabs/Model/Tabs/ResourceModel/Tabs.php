<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs\ResourceModel;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Model\Source\Type;
use Amasty\CustomTabs\Model\Tabs\Tabs as TabsModel;
use Amasty\CustomTabs\Setup\Patch\Data\TabsContentScopeMigration as TabsFields;
use Magento\Framework\DB\Helper;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\Store;

class Tabs extends AbstractDb
{
    public const TABLE_NAME = 'amasty_customtabs_tabs';
    public const CONTENT_TABLE_NAME = 'amasty_customtabs_tabs_content';

    /**
     * @var Helper
     */
    private $dbHelper;

    public function __construct(
        Helper $dbHelper,
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->dbHelper = $dbHelper;
    }

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, TabsInterface::TAB_ID);
    }

    /**
     * @inheritdoc
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);

        return $select;
    }

    protected function _afterSave(AbstractModel $object)
    {
        $this->updateContent($object);

        return parent::_afterSave($object);
    }

    /**
     * @var TabInterface $tab
     */
    private function updateContent(AbstractModel $tab): void
    {
        $connection = $this->getConnection();
        $table = $this->getTable(Tabs::CONTENT_TABLE_NAME);

        $tabId = $tab->getTabId();
        $storeId = $tab->getStoreId() ?? Store::DEFAULT_STORE_ID;
        $useDefault = $tab->getUseDefault();

        if ($useDefault) {
            $tab->setStatus($useDefault['status'] === '0' ? $tab->getStatus() : null);
            $tab->setTabTitle($useDefault['tab_title'] === '0' ? $tab->getTabTitle() : null);
            $tab->setContent($useDefault['content'] === '0' ? $tab->getContent() : null);
        } else {
            $tab->setTabTitle($tab->getTabTitle());
            $tab->setContent($tab->getContent());
            $tab->setStatus($tab->getStatus());
        }

        $select = $connection->select()
            ->from($table)
            ->where('tab_id = ?', $tabId)
            ->where('store_id = ?', $storeId);

        $exists = $connection->fetchRow($select);

        if ($exists) {
            $connection->update(
                $table,
                $this->filterTabData($tab),
                ['tab_id = ?' => $tabId, 'store_id = ?' => $storeId]
            );
        } else {
            $connection->insertOnDuplicate(
                $table,
                array_merge(['tab_id' => $tabId, 'store_id' => $storeId], $this->filterTabData($tab))
            );
        }
    }

    /**
     * @var TabInterface $tab
     */
    private function filterTabData(AbstractModel $tab): array
    {
        return array_intersect_key(
            $tab->getData(),
            array_flip(TabsFields::MIGRATION_FIELDS)
        );
    }

    /**
     * @param TabsModel $object
     */
    private function updateStores($object)
    {
        $connection = $this->getConnection();
        $tabId = $object->getTabId();

        $table = $this->getTable(TabsInterface::STORE_TABLE_NAME);
        $select = $select = $connection->select()
            ->from($table, 'store_id')
            ->where(TabsInterface::TAB_ID . ' = ?', $tabId);
        $oldData = $connection->fetchCol($select);
        $newData = $object->getStores();

        if (is_array($newData)) {
            $toDelete = array_diff($oldData, $newData);
            $toInsert = array_diff($newData, $oldData);
            $toInsert = array_diff($toInsert, ['']);
        } else {
            $toDelete = $oldData;
            $toInsert = null;
        }

        if (!empty($toDelete)) {
            $deleteSelect = clone $select;
            $deleteSelect->where('store_id IN (?)', $toDelete);
            $query = $connection->deleteFromSelect($deleteSelect, $table);
            $connection->query($query);
        }
        if (!empty($toInsert)) {
            $insertArray = [];
            foreach ($toInsert as $value) {
                $insertArray[] = [TabsInterface::TAB_ID => $tabId, 'store_id' => $value];
            }
            $connection->insertMultiple($table, $insertArray);
        }
    }

    /**
     * @param \Magento\Framework\DB\Select $select
     * @param bool $group
     */
    public function joinStores($select, $group = true)
    {
        $table = $this->getTable(self::TABLE_NAME);
        $select->joinLeft(
            ['stores_table' => $this->getTable(Tabs::CONTENT_TABLE_NAME)],
            $table . '.' . TabsInterface::TAB_ID . ' = stores_table.' . TabsInterface::TAB_ID,
            []
        );
        if ($group) {
            $this->dbHelper->addGroupConcatColumn(
                $select,
                'stores',
                'DISTINCT stores_table.store_id'
            );
        }
    }

    /**
     * @param int $storeId
     * @param array $loadedTabsIds
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteOutdatedTabs($storeId, $loadedTabsIds)
    {
        $connection = $this->getConnection();

        $select = $select = $connection->select()
            ->from($this->getMainTable(), 'tab_id')
            ->where($this->getMainTable() . '.' . TabsInterface::TAB_ID . ' NOT IN (?)', $loadedTabsIds)
            ->where(TabsInterface::TAB_TYPE . ' != ?', Type::CUSTOM);
        $this->joinStores($select);
        $select->where('store_id = ?', $storeId);

        $query = $connection->deleteFromSelect($select, $this->getMainTable());
        $connection->query($query);
    }
}
