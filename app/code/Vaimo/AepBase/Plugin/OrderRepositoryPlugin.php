<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Plugin;

use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Vaimo\AepBase\Model\ResourceModel\Order as ResourceModel;
use Vaimo\AepEventStreaming\Api\ConfigInterface;

class OrderRepositoryPlugin
{
    private ConfigInterface $config;
    private ResourceModel $resourceModel;

    public function __construct(ConfigInterface $config, ResourceModel $resourceModel)
    {
        $this->config = $config;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface[]|null
     */
    public function beforeSave(OrderRepositoryInterface $subject, OrderInterface $order): ?array
    {
        if ($order->getId() || !$order->getCustomerId()) {
            return null;
        }

        if ($order->getCreatedAt()) { // should be always null, but who knows
            $orderDate = \DateTime::createFromFormat(DateTime::DATETIME_PHP_FORMAT, $order->getCreatedAt());
        } else {
            $orderDate = new \DateTime();
        }

        $previousOrderDate = $this->getCustomerPreviousOrderDate(
            (int) $order->getCustomerId(),
            $orderDate
        );

        if ($previousOrderDate === null) {
            return null;
        }

        $previousOrderDateFormatted = $previousOrderDate->format(DateTime::DATE_PHP_FORMAT);
        $order->getExtensionAttributes()->setCustomerPreviousOrderDate($previousOrderDateFormatted);
        $order->setData(
            ResourceModel::CUSTOMER_PREVIOUS_ORDER_DATE,
            $previousOrderDate->format($previousOrderDateFormatted)
        );

        return [$order];
    }

    private function getCustomerPreviousOrderDate(int $customerId, \DateTime $dateTo): ?\DateTime
    {
        $previousOrderDate = $this->resourceModel->getCustomerPreviousOrderDate(
            $customerId,
            $dateTo
        );

        if ($previousOrderDate === null) {
            return null;
        }

        $previousOrderDate = \DateTime::createFromFormat(DateTime::DATETIME_PHP_FORMAT, $previousOrderDate);

        if ($previousOrderDate === false) {
            return null;
        }

        return $previousOrderDate;
    }

    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        if (!$this->config->isEnabled()) {
            return $order;
        }

        $order->getExtensionAttributes()->setCustomerPreviousOrderDate(
            $order->getData(ResourceModel::CUSTOMER_PREVIOUS_ORDER_DATE)
        );

        return $order;
    }
}
