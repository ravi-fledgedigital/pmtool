<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2021 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\GiftCardProductDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Gift card options data query for product data exporter
 */
class OptionQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @return Select
     */
    public function getQuery(array $arguments): Select
    {
        $productIds = $arguments['productId'] ?? [];
        $storeViewCodes = $arguments['storeViewCode'] ?? [];

        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                ['productId' => 'cpe.entity_id']
            )
            ->joinInner(
                ['s' => $this->resourceConnection->getTableName('store')],
                's.store_id != 0',
                ['storeViewCode' => 's.code']
            )
            ->joinInner(
                ['mga' => $this->resourceConnection->getTableName('magento_giftcard_amount')],
                'mga.row_id = cpe.row_id and mga.website_id IN (s.website_id, 0)',
                [
                    'attribute_id' => 'mga.attribute_id',
                    'value' => 'mga.value'
                ]
            )
            ->where('s.code IN (?)', $storeViewCodes)
            ->where('cpe.entity_id IN (?)', $productIds);
        return $select;
    }
}
