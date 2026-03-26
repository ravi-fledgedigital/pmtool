<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Model\ResourceModel\Page;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Magento\Store\Model\Store;
use Magento\Framework\DB\Select;
use Mirasvit\LandingPage\Api\Data\PageStoreInterface;
use Zend_Db_Expr;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'page_id';

    protected $_eventPrefix = 'mst_landing_page_collection';

    protected $_eventObject = 'landing_page_collection';

    protected const BIND_PARAM_STORE_ID = ':store_id';

    protected const TABLE_ALIAS         = 'main_table';

    protected const TABLE_STORE_ALIAS   = 'store';

    protected function _construct()
    {
        $this->_init('Mirasvit\LandingPage\Model\Page', 'Mirasvit\LandingPage\Model\ResourceModel\Page');
    }

    /**
     * Returns page enabled for the store
     */
    public function addStoreFilter(int $storeId): Collection
    {
        $this->addFieldToFilter(
            [PageInterface::STORE_IDS, PageInterface::STORE_IDS],
            [
                ['finset' => 0],
                ['finset' => $storeId],
            ]
        );

        return $this->addStoreConfigFilter($storeId);
    }

    /**
     * Returns store-oriented entity data (name, URL key, etc.)
     */
    public function addStoreConfigFilter(int $storeId): self
    {
        $this->addBindParam(self::BIND_PARAM_STORE_ID, $storeId);

        return $this;
    }

    protected function _initSelect(): self
    {
        parent::_initSelect();

        $mainColumns = [];
        foreach (PageInterface::GLOBAL_FIELDS as $field) {
            $mainColumns[$field] = self::TABLE_ALIAS . '.' . $field;
        }

        $storeFields = PageInterface::STORE_FIELDS;

        $soreColumns = [
            PageInterface::STORE_ID => new Zend_Db_Expr(
                sprintf('IFNULL(%s, %s)', PageInterface::STORE_ID, Store::DEFAULT_STORE_ID)
            ),
        ];
        foreach ($storeFields as $field) {
            $soreColumns[$field] = new Zend_Db_Expr(
                sprintf('IFNULL(%s.%s, %s.%s)', self::TABLE_STORE_ALIAS, $field, self::TABLE_ALIAS, $field)
            );
        }

        $this->getSelect()
            ->reset(Select::COLUMNS)
            ->columns($mainColumns)
            ->joinLeft(
                [self::TABLE_STORE_ALIAS => $this->getTable(PageStoreInterface::TABLE_NAME)],
                sprintf(
                    '%s.%s = %s.%s and %s.%s = %s',
                    self::TABLE_ALIAS,
                    PageInterface::PAGE_ID,
                    self::TABLE_STORE_ALIAS,
                    PageStoreInterface::PAGE_ID,
                    self::TABLE_STORE_ALIAS,
                    PageInterface::STORE_ID,
                    self::BIND_PARAM_STORE_ID
                ),
                $soreColumns
            );

        $combinedTable = new Zend_Db_Expr(sprintf('(%s)', $this->getSelect()->assemble()));

        $this->getSelect()
            ->reset()
            ->from([self::TABLE_ALIAS => $combinedTable]);

        return $this;
    }

    // avoiding of the getCollection method calling by other modules without binding :store_id
    protected function _beforeLoad()
    {
        parent::_beforeLoad();
        
        $query = $this->getSelect()->assemble();

        if (strpos($query, 'FIND_IN_SET') === false) {
            $this->addStoreFilter(0);
        }

        return $this;
    }

}
