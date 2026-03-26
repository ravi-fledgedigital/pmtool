<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs\ResourceModel;

use Magento\Store\Model\Store;

/**
 * Trait CollectionTrait
 */
trait CollectionTrait
{
    public function addDefaultStore(): self
    {
        $this->getSelect()->joinLeft(
            ['store' => $this->getTable(Tabs::CONTENT_TABLE_NAME)],
            sprintf(
                'store.%s = main_table.%s AND store.store_id = %s',
                'tab_id',
                'tab_id',
                Store::DEFAULT_STORE_ID
            ),
            ['*']
        );
        return $this;
    }

    public function addStore(int $storeId): self
    {
        $this->getSelect()->joinLeft(
            ['noDefaultStore' => $this->getTable(Tabs::CONTENT_TABLE_NAME)],
            'noDefaultStore.tab_id = main_table.tab_id AND noDefaultStore.store_id = ' . $storeId,
            []
        );
        return $this;
    }

    public function addStoreWithDefault(int $storeId): self
    {
        $this->addDefaultStore()->addStore($storeId);

        foreach (Collection::MULTI_STORE_FIELDS_MAP as $key => $field) {
            $this->getSelect()->columns([$key => $field]);
            $this->getSelect()->columns([
                $key . '_from_default' => new \Zend_Db_Expr("IF(noDefaultStore.$key IS NULL, 1, 0)")
            ]);
        }

        $this->getSelect()->group('main_table.tab_id');

        return $this;
    }
}
