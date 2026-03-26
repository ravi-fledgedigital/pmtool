<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Model\Rule\Condition\Product\Attributes;

use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatusResource;
use Magento\Framework\DB\Select;
use Magento\Rule\Model\Condition\Product\AbstractProduct as ProductCondition;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;
use Magento\TargetRule\Model\ResourceModel\Index as IndexResource;
use Zend_Db_Expr;

class QuantityAndStockStatus implements SqlBuilderInterface
{
    /**
     * @param IndexResource $indexResource
     * @param StoreRepositoryInterface $storeRepository
     * @param StockStatusResource $stockStatusResource
     */
    public function __construct(
        private IndexResource $indexResource,
        private StoreRepositoryInterface $storeRepository,
        private StockStatusResource $stockStatusResource
    ) {
    }

    /**
     * @inheritdoc
     */
    public function generateWhereClause(
        ProductCondition $condition,
        array &$bind,
        int $storeId,
        Select $select
    ): Zend_Db_Expr {
        $store = $this->storeRepository->getById($storeId);
        $select->from(['products' => $this->indexResource->getTable('catalog_product_entity')], []);
        $this->stockStatusResource->addStockStatusToSelect($select, $store->getWebsite());

        $operator = $condition->getOperator();
        $where = match ($condition->getValueType()) {
            Attributes::VALUE_TYPE_SAME_AS => $this->indexResource->getOperatorBindCondition(
                'is_salable',
                'is_salable',
                $operator,
                $bind
            ),
            default => $this->indexResource->getOperatorCondition('is_salable', $operator, $condition->getValue()),
        };
        $select->where('products.entity_id = e.entity_id')
            ->having($where);

        return new Zend_Db_Expr(sprintf('EXISTS (%s)', $select->assemble()));
    }
}
