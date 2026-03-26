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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Brand\Model\ResourceModel\BrandPage\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Brand\Model\Config\GeneralConfig;
use Mirasvit\Brand\Model\ResourceModel\BrandPage\Collection as BaseCollection;
use \Magento\Framework\DB\Sql\Expression;

class Collection extends BaseCollection implements SearchResultInterface
{
    private $config;

    protected $aggregations;

    private bool $isSelectInitialized = false;

    public function __construct(
        GeneralConfig $config,
        StoreManagerInterface $storeManager,
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        ?AdapterInterface $connection = null,
        ?AbstractDb $resource = null
    ) {
        $this->config = $config;
        parent::__construct(
            $storeManager,
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    public function getSearchCriteria()
    {
        return null;
    }

    public function setSearchCriteria(?SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    public function getTotalCount()
    {
        return $this->getSize();
    }

    public function setTotalCount($totalCount)
    {
        return $this;
    }

    public function setItems(?array $items = null)
    {
        return $this;
    }

    protected function _initSelect()
    {
        if ($this->isSelectInitialized) {
            return $this;
        }
        $this->isSelectInitialized = true;

        parent::_initSelect();

        $brandAttributeCode = $this->config->getBrandAttribute();

        if (!$brandAttributeCode) {
            return $this;
        }

        $connection = $this->getConnection();

        $attributeId = (int)$connection->fetchOne(
            $connection->select()
                ->from(['ea' => $this->getTable('eav_attribute')], ['attribute_id'])
                ->where('ea.attribute_code = ?', $brandAttributeCode)
                ->where('ea.entity_type_id = ?', 4) // entity_type_id 4 = product
        );

        $productCountSubSelect = $connection->select()
            ->from(
                ['pei' => $this->getTable('catalog_product_entity_int')],
                ['attribute_option_id' => 'value', 'product_count' => new Expression('COUNT(*)')]
            )
            ->where('pei.attribute_id = ?', $attributeId)
            ->group('pei.value');

        $this->getSelect()
            ->joinLeft(
                ['product_count_table' => $productCountSubSelect],
                'main_table.attribute_option_id = product_count_table.attribute_option_id',
                ['product_count' => new Expression('IFNULL(product_count_table.product_count, 0)')]
            );

        $this->addFilterToMap('product_count', 'product_count');
        $this->addFilterToMap('store_id', 'main_table.store_ids');

        return $this;
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'product_count') {
            $this->_initSelect();

            $expr = new Expression('IFNULL(product_count_table.product_count, 0)');

            $this->getSelect()->where($this->getConnection()->prepareSqlCondition($expr, $condition));

            return $this;
        }

        return parent::addFieldToFilter($field, $condition);
    }

    public function getSelectCountSql()
    {
        $countSelect = clone $this->getSelect();

        $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $countSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $countSelect->reset(\Magento\Framework\DB\Select::GROUP);
        $countSelect->reset(\Magento\Framework\DB\Select::HAVING);

        $countSelect->columns('COUNT(DISTINCT main_table.brand_page_id)');

        return $countSelect;
    }
}
