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
use Magento\AdminUiSdkCustomFees\Model\CustomFeesRepository;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface as BaseCreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 * Plugin to add custom fees to credit memo
 */
class CreditmemoRepositoryInterfacePlugin
{
    /**
     * @param Config $config
     * @param CreditmemoExtensionFactory $creditmemoExtensionFactory
     * @param CustomFeesRepository $customFeesRepository
     */
    public function __construct(
        private Config $config,
        private CreditmemoExtensionFactory $creditmemoExtensionFactory,
        private CustomFeesRepository $customFeesRepository
    ) {
    }

    /**
     * After get function to get custom fees
     *
     * @param BaseCreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $entity
     * @return CreditmemoInterface
     */
    public function afterGet(
        BaseCreditmemoRepositoryInterface $subject,
        CreditmemoInterface $entity,
    ): CreditmemoInterface {
        if ($this->config->isAdminUISDKEnabled()) {
            $order = $entity->getOrder();
            $customFees = $this->customFeesRepository->getByOrderId($order->getId());
            $creditmemoCustomFees = [];
            foreach ($customFees as $customFee) {
                if ($customFee->getCreditmemoId() === $entity->getEntityId()) {
                    $creditmemoCustomFees[] = $customFee;
                }
            }
            $extensionAttributes = $order->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->creditmemoExtensionFactory->create();
            }
            $extensionAttributes->setData('custom_fees', $creditmemoCustomFees);
            $order->setExtensionAttributes($extensionAttributes);
            $entity->setOrder($order);
        }
        return $entity;
    }

    /**
     * After save function to add custom fees to sales credit memo
     *
     * @param BaseCreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $entity
     * @return CreditmemoInterface
     * @throws LocalizedException
     */
    public function afterSave(
        BaseCreditmemoRepositoryInterface $subject,
        CreditmemoInterface $entity
    ): CreditmemoInterface {
        if ($this->config->isAdminUISDKEnabled()) {
            $customFees = $entity->getOrder()->getExtensionAttributes()->getCustomFees();
            foreach ($customFees as $customFee) {
                $this->customFeesRepository->addRefundedAmountAndCreditMemoId(
                    (int)$customFee->getEntityId(),
                    (float)$customFee[CustomFeesInterface::FIELD_FEE_AMOUNT_REFUNDED],
                    (float)$customFee[CustomFeesInterface::FIELD_BASE_FEE_AMOUNT_REFUNDED],
                    $entity->getEntityId()
                );
            }
        }
        return $entity;
    }
}
