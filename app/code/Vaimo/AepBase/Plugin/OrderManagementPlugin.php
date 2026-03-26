<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Psr\Log\LoggerInterface;
use Vaimo\AepBase\Api\ConfigInterface;
use Vaimo\AepBase\Model\ResourceModel\Order as ResourceModel;
use Vaimo\AepBase\Setup\Patch\Data\CustomerDataAggregationAttributes as Attributes;

class OrderManagementPlugin
{
    private ConfigInterface $config;
    private CustomerRepositoryInterface $customerRepository;
    private ResourceModel $resourceModel;
    private LoggerInterface $logger;

    public function __construct(
        ConfigInterface $config,
        CustomerRepositoryInterface $customerRepository,
        ResourceModel $resourceModel,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->customerRepository = $customerRepository;
        $this->resourceModel = $resourceModel;
        $this->logger = $logger;
    }

    public function afterPlace(
        OrderManagementInterface $subject,
        OrderInterface $order
    ): OrderInterface {
        if (!$this->config->isEnabled() || $order->getCustomerIsGuest()) {
            return $order;
        }

        try {
            $customer = $this->customerRepository->getById($order->getCustomerId());
            $orderData = $this->resourceModel->getCustomerOrdersData((int) $customer->getId());
            $extensionAttributes = $customer->getExtensionAttributes();

            $lifetimeValueAmt = $orderData[Attributes::TOTAL_ORDER_AMT] ?? 0;
            $lifetimeValueAmt -= $extensionAttributes->getAepTotalReturnOrderAmt();

            $extensionAttributes
                ->setAepTotalCouponCount($orderData[Attributes::TOTAL_COUPON_COUNT] ?? 0)
                ->setAepTotalOrderAmt($orderData[Attributes::TOTAL_ORDER_AMT] ?? 0)
                ->setAepTotalOrderCnt($orderData[Attributes::TOTAL_ORDER_CNT] ?? 0)
                ->setAepLastOrderDate($orderData[Attributes::LAST_ORDER_DATE] ?? null)
                ->setAepFirstOrderDate($orderData[Attributes::FIRST_ORDER_DATE] ?? null)
                ->setAepLifetimeValueAmt($lifetimeValueAmt)
                ->setAepCartAbandonedProducts('');

            $this->customerRepository->save($customer);
        } catch (\Throwable $e) {
            $this->logger->critical($e);
        }

        return $order;
    }
}
