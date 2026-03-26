<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdminUiSdkCustomFees\Plugin\Sales\Api;

use Magento\AdminUiSdkCustomFees\Model\CustomFeesRepository;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface as BaseInvoiceRepositoryInterface;

/**
 * Plugin to add custom fees to invoice
 */
class InvoiceRepositoryInterfacePlugin
{
    /**
     * @param Config $config
     * @param InvoiceExtensionFactory $invoiceExtensionFactory
     * @param CustomFeesRepository $customFeesRepository
     */
    public function __construct(
        private Config $config,
        private InvoiceExtensionFactory $invoiceExtensionFactory,
        private CustomFeesRepository  $customFeesRepository
    ) {
    }

    /**
     * After get function to get custom fees
     *
     * @param BaseInvoiceRepositoryInterface $subject
     * @param InvoiceInterface $entity
     * @return InvoiceInterface
     */
    public function afterGet(
        BaseInvoiceRepositoryInterface $subject,
        InvoiceInterface $entity,
    ): InvoiceInterface {
        if ($this->config->isAdminUISDKEnabled()) {
            $order = $entity->getOrder();
            $customFees = $this->customFeesRepository->getByOrderId($order->getId());
            $invoiceCustomFees = [];
            foreach ($customFees as $customFee) {
                if ($customFee->getInvoiceId() === $entity->getEntityId()) {
                    $invoiceCustomFees[] = $customFee;
                }
            }
            $extensionAttributes = $order->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->invoiceExtensionFactory->create();
            }
            $extensionAttributes->setData('custom_fees', $invoiceCustomFees);
            $order->setExtensionAttributes($extensionAttributes);
            $entity->setOrder($order);
        }
        return $entity;
    }
}
