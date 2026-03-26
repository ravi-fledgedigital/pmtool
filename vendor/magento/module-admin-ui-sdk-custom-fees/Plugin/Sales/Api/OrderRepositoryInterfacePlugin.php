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

use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\AdminUiSdkCustomFees\Model\CustomFeesFactory;
use Magento\AdminUiSdkCustomFees\Model\CustomFeesRepository;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface as BaseOrderRepositoryInterface;

/**
 * Plugin to add custom fees to order
 */
class OrderRepositoryInterfacePlugin
{
    /**
     * @param Config $config
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param CustomFeesFactory $customFeesFactory
     * @param CustomFeesRepository $customFeesRepository
     */
    public function __construct(
        private Config $config,
        private OrderExtensionFactory $orderExtensionFactory,
        private CustomFeesFactory     $customFeesFactory,
        private CustomFeesRepository  $customFeesRepository
    ) {
    }

    /**
     * After get function to get custom fees
     *
     * @param BaseOrderRepositoryInterface $subject
     * @param OrderInterface $entity
     * @return OrderInterface
     */
    public function afterGet(
        BaseOrderRepositoryInterface $subject,
        OrderInterface $entity,
    ): OrderInterface {
        if ($this->config->isAdminUISDKEnabled()) {
            $customFees = $this->customFeesRepository->getByOrderId($entity->getId());
            $extensionAttributes = $entity->getExtensionAttributes();
            $extensionAttributes->setCustomFees($customFees->getItems());
            $entity->setExtensionAttributes($extensionAttributes);
        }
        return $entity;
    }

    /**
     * After save function to add custom fees to sales order
     *
     * @param BaseOrderRepositoryInterface $subject
     * @param OrderInterface $result
     * @return OrderInterface
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    public function afterSave(
        BaseOrderRepositoryInterface $subject,
        OrderInterface $result
    ): OrderInterface {
        $salesOrderModels = [];
        if (!$this->config->isAdminUISDKEnabled()) {
            return $result;
        }
        $baseToOrderRate = $result->getBaseToOrderRate() ?? 1;
        foreach ((array)$result->getCustomFees() as $customFee) {
            $baseCustomFeeAmount = $customFee['value'];
            $customFeeAmount = $baseCustomFeeAmount * $baseToOrderRate;
            $salesOrderModel = $this->customFeesFactory->create();
            $salesOrderModel->setData('order_id', $result->getEntityId());
            $salesOrderModel->setData(CustomFeesInterface::FIELD_FEE_CODE, $customFee['id']);
            $salesOrderModel->setData(CustomFeesInterface::FIELD_FEE_LABEL, $customFee['label']);
            $salesOrderModel->setData(CustomFeesInterface::FIELD_FEE_AMOUNT, $customFeeAmount);
            $salesOrderModel->setData(CustomFeesInterface::FIELD_BASE_FEE_AMOUNT, $baseCustomFeeAmount);
            $salesOrderModel->setData(
                CustomFeesInterface::FIELD_APPLY_FEE_ON_LAST_INVOICE,
                $customFee['applyFeeOnLastInvoice'] ?? false
            );
            $salesOrderModel->setData(
                CustomFeesInterface::FIELD_APPLY_FEE_ON_LAST_CREDITMEMO,
                $customFee['applyFeeOnLastCreditMemo'] ?? true
            );
            $this->customFeesRepository->save($salesOrderModel);
            $salesOrderModels[] = $salesOrderModel;
        }

        $extensionAttributes = $result->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->orderExtensionFactory->create();
        }

        $extensionAttributes->setData('custom_fees', $salesOrderModels);
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }
}
