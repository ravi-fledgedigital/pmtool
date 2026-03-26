<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Model\ResourceModel;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\ResourceModel\Quote as ResourceModel;
use Vaimo\AepBase\Setup\Patch\Data\CustomerDataAggregationAttributes as Attributes;

class Quote
{
    private ResourceModel $resourceModel;
    private EavConfig $eavConfig;

    public function __construct(ResourceModel $resourceModel, EavConfig $eavConfig)
    {
        $this->resourceModel = $resourceModel;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @return string[][]
     * @throws LocalizedException
     */
    public function getAbandonedQuotesData(string $dateFrom, string $dateTo): array
    {
        $connection = $this->resourceModel->getConnection();
        $select = $connection->select();
        $select->from(['quote' => $this->resourceModel->getMainTable()], ['quote_id' => 'entity_id', 'customer_id']);
        $select->joinLeft(
            ['store' => $connection->getTableName('store')],
            'store.store_id = quote.store_id',
            ['store_code' => 'store.code']
        );
        $select->joinLeft(
            ['quote_item' => $connection->getTableName('quote_item')],
            'quote_item.quote_id = quote.entity_id AND quote_item.parent_item_id IS NULL',
            [
                'sku' => 'quote_item.sku',
                'item_id' => 'quote_item.item_id',
            ]
        );

        $select->where(new \Zend_Db_Expr('quote.customer_id IS NOT NULL'));
        $select->where('quote.is_active = 1');
        $select->where('quote.updated_at >= ?', $dateFrom);
        $select->where('quote.updated_at <= ?', $dateTo);
        $select->where('items_count > 0');

        $select->order('quote_item.item_id DESC');

        return $this->resourceModel->getConnection()->fetchAll($select);
    }

    /**
     * @param int[] $customersIds
     * @return string[][]
     * @throws LocalizedException
     */
    public function getAbandonedCartSkus(array $customersIds): array
    {
        $connection = $this->resourceModel->getConnection();
        $select = $connection->select();

        $attribute = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            Attributes::CART_ABANDONED_PRODUCTS
        );
        $tableName = $attribute->getBackendTable();

        $select->from($tableName, ['customer_id' => 'entity_id', 'skus' => 'value']);
        $select->where('entity_id IN (?)', $customersIds);
        $select->where('attribute_id = ?', $attribute->getAttributeId());

        return $connection->fetchAssoc($select);
    }

    /**
     * @param string[][] $skusData Format: ['customer_id' => 'SKUS']
     * @return void
     */
    public function updateAbandonedCartSkus(array $skusData): void
    {
        $attribute = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            Attributes::CART_ABANDONED_PRODUCTS
        );

        $data = [];
        $customersIds = [];

        foreach ($skusData as $customerId => $skus) {
            $customersIds[] = $customerId;
            $data[] = [
                'entity_id' => $customerId,
                'attribute_id' => $attribute->getAttributeId(),
                'value' => $skus,
            ];
        }

        $this->resourceModel->getConnection()->insertOnDuplicate(
            $attribute->getBackendTable(),
            $data
        );

        $this->resourceModel->getConnection()->update(
            $this->resourceModel->getConnection()->getTableName('customer_entity'),
            ['updated_at' => new \Zend_Db_Expr('NOW()')],
            ['entity_id IN(?)' => $customersIds]
        );
    }
}
