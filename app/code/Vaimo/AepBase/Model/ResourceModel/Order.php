<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order as ResourceModel;
use Vaimo\AepBase\Setup\Patch\Data\CustomerDataAggregationAttributes as Attributes;

class Order
{
    public const CUSTOMER_PREVIOUS_ORDER_DATE = 'customer_previous_order_date';

    private ResourceModel $resource;

    public function __construct(ResourceModel $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param int $customerId
     * @return string[]
     * @throws LocalizedException
     */
    public function getCustomerOrdersData(int $customerId): array
    {
        $result = $this->getCustomersOrdersData([$customerId]);

        return $result[$customerId] ?? [];
    }

    /**
     * @param int[]|null $customersIds
     * @return string[][]
     * @throws LocalizedException
     */
    public function getCustomersOrdersData(?array $customersIds = null): array
    {
        $select = $this->resource->getConnection()->select();

        $couponCount = new \Zend_Db_Expr('COUNT(coupon_code)');
        $totalOrderAmt = new \Zend_Db_Expr('SUM(base_grand_total)');
        $totalOrderCnt = new \Zend_Db_Expr('COUNT(*)');
        $lastOrderDate = new \Zend_Db_Expr('MAX(created_at)');
        $firstOrderDate = new \Zend_Db_Expr('MIN(created_at)');

        $select->from(
            $this->resource->getMainTable(),
            [
                'customer_id' => 'customer_id',
                Attributes::TOTAL_COUPON_COUNT => $couponCount,
                Attributes::TOTAL_ORDER_AMT => $totalOrderAmt,
                Attributes::TOTAL_ORDER_CNT => $totalOrderCnt,
                Attributes::LAST_ORDER_DATE => $lastOrderDate,
                Attributes::FIRST_ORDER_DATE => $firstOrderDate,
            ]
        );

        if ($customersIds) {
            $select->where('customer_id IN(?)', $customersIds);

            // phpcs:ignore Vaimo.ControlStructures.NestedIf.Found
            if (count($customersIds) > 1) {
                $select->group('customer_id');
            }
        }

        return $this->resource->getConnection()->fetchAssoc($select);
    }

    public function getCustomerPreviousOrderDate(int $customerId, \DateTime $dateTo): ?string
    {
        $select = $this->resource->getConnection()->select();

        $select->from($this->resource->getMainTable(), ['created_at']);
        $select->where(OrderInterface::CUSTOMER_ID . ' = ?', $customerId);
        $select->where(OrderInterface::CREATED_AT . ' < ?', $dateTo->format(DateTime::DATETIME_PHP_FORMAT));
        $select->limitPage(1, 1);
        $select->order(OrderInterface::CREATED_AT . ' DESC');

        $result = $this->resource->getConnection()->fetchOne($select);

        return $result ?: null;
    }
}
