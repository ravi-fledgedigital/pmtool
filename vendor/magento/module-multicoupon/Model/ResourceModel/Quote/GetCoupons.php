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

namespace Magento\Multicoupon\Model\ResourceModel\Quote;

use Magento\Framework\App\ResourceConnection;
use Magento\Multicoupon\Api\Quote\GetCouponsInterface;

class GetCoupons implements GetCouponsInterface
{
    private const MAIN_TABLE = 'quote_coupons';
    private const QUOTE_ID = 'quote_id';
    private const COUPON_CODE = 'coupon_code';

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
    public function execute(string $cartId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()->from(
            [$this->resourceConnection->getTableName(self::MAIN_TABLE)],
            self::COUPON_CODE
        )->where(
            self::QUOTE_ID . ' = ?',
            $cartId
        );

        return $connection->fetchCol($select);
    }
}
