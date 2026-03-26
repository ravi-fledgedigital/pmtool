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

namespace Magento\AdminUiSdkCustomFees\Model;

use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\AdminUiSdkCustomFees\Api\CustomFeesRepositoryInterface;
use Magento\AdminUiSdkCustomFees\Model\ResourceModel\CustomFees as ResourceModel;
use Magento\AdminUiSdkCustomFees\Model\ResourceModel\SalesOrder\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class repository for custom fees
 */
class CustomFeesRepository implements CustomFeesRepositoryInterface
{
    /**
     * @param ResourceModel $resourceModel
     * @param CustomFeesFactory $customFeesFactory
     * @param CollectionFactory $salesOrderCollectionFactory
     */
    public function __construct(
        private ResourceModel $resourceModel,
        private CustomFeesFactory $customFeesFactory,
        private CollectionFactory $salesOrderCollectionFactory
    ) {
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function getById(int $id): CustomFeesInterface
    {
        $salesOrder = $this->customFeesFactory->create();
        $this->resourceModel->load($salesOrder, $id);
        if (!$salesOrder->getId()) {
            throw new NoSuchEntityException(__('Unable to find Sales Order with ID "%1"', $id));
        }
        return $salesOrder;
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    public function save(CustomFeesInterface $salesOrder): void
    {
        $this->resourceModel->save($salesOrder);
    }

    /**
     * @inheritdoc
     */
    public function getByOrderId(string $orderId)
    {
        $collection = $this->salesOrderCollectionFactory->create();
        return $collection->addFieldToFilter(CustomFeesInterface::FIELD_ORDER_ID, ['eq' => $orderId]);
    }

    /**
     * @inheritdoc
     */
    public function getByInvoiceId(string $invoiceId)
    {
        $collection = $this->salesOrderCollectionFactory->create();
        return $collection->addFieldToFilter(CustomFeesInterface::FIELD_INVOICE_ID, ['eq' => $invoiceId]);
    }

    /**
     * @inheritdoc
     */
    public function getByCreditMemoId(string $creditMemoId)
    {
        $collection = $this->salesOrderCollectionFactory->create();
        return $collection->addFieldToFilter(CustomFeesInterface::FIELD_CREDITMEMO_ID, ['eq' => $creditMemoId]);
    }
    
    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function addInvoicedAmount(int $id, string $invoiceId, float $amount, float $baseAmount): void
    {
        $customFee = $this->getById($id);
        $customFee->setInvoiceId($invoiceId);
        $customFee->setCustomFeeAmountInvoiced($amount);
        $customFee->setBaseCustomFeeAmountInvoiced($baseAmount);
        $this->save($customFee);
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addRefundedAmountAndCreditMemoId(
        int $id,
        float $amount,
        float $baseAmount,
        string $creditMemoId
    ): void {
        $customFee = $this->getById($id);
        $customFee->setCustomFeeAmountRefunded($amount);
        $customFee->setBaseCustomFeeAmountRefunded($baseAmount);
        $customFee->setCreditmemoId($creditMemoId);
        $this->save($customFee);
    }
}
