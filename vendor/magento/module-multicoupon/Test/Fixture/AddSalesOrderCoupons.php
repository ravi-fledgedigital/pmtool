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

namespace Magento\Multicoupon\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Multicoupon\Api\SalesOrder\AddCouponsInterface;
use Magento\Multicoupon\Api\SalesOrder\RemoveAllCouponsInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class AddSalesOrderCoupons implements RevertibleDataFixtureInterface
{
    /**
     * @var AddCouponsInterface
     */
    private AddCouponsInterface $addCoupons;

    /**
     * @var RemoveAllCouponsInterface
     */
    private RemoveAllCouponsInterface $removeAllCoupons;

    /**
     * @var DataObjectFactory
     */
    private DataObjectFactory $dataObjectFactory;

    /**
     * @param AddCouponsInterface $addCoupons
     * @param RemoveAllCouponsInterface $removeAllCoupons
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        AddCouponsInterface $addCoupons,
        RemoveAllCouponsInterface $removeAllCoupons,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->addCoupons = $addCoupons;
        $this->removeAllCoupons = $removeAllCoupons;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters
     * <pre>
     *    $data = [
     *      'order_id'    => (string) Order ID. Required.
     *      'coupon_codes' => (array) Coupon Codes. Required.
     *    ]
     * </pre>
     */
    public function apply(array $data = []): ?DataObject
    {
        $this->addCoupons->execute($data['order_id'], $data['coupon_codes']);
        return $this->dataObjectFactory->create(['data' => [$this->addCoupons]]);
    }

    public function revert(DataObject $data): void
    {
        if ($data->getOrderId()) {
            $this->removeAllCoupons->execute($data->getOrderId());
        }
    }
}
