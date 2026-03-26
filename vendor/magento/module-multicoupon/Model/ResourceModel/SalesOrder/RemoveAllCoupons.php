<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained from
 * Adobe.
 */
declare(strict_types=1);

namespace Magento\Multicoupon\Model\ResourceModel\SalesOrder;

use Magento\Framework\App\ResourceConnection;
use Magento\Multicoupon\Api\SalesOrder\RemoveAllCouponsInterface;

class RemoveAllCoupons implements RemoveAllCouponsInterface
{
    private const MAIN_TABLE = 'sales_order_coupons';
    private const SALES_ORDER_ID = 'sales_order_id';

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(string $orderId): void
    {
        $this->resourceConnection->getConnection()->delete(
            $this->resourceConnection->getTableName(self::MAIN_TABLE),
            [self::SALES_ORDER_ID . '=?' => $orderId]
        );
    }
}
