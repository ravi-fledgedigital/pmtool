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
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Class for custom fees model object
 */
class CustomFees extends AbstractModel implements CustomFeesInterface, IdentityInterface
{
    /**
     * Constructor
     */
    public function _construct(): void
    {
        $this->_init(ResourceModel\CustomFees::class);
    }

    /**
     * @inheritDoc
     */
    public function getIdentities(): array
    {
        return [$this->getId()];
    }

    /**
     * @inheritDoc
     */
    public function getId(): ?string
    {
        return parent::getData(self::FIELD_ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): string
    {
        return parent::getData(self::FIELD_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(string $orderId): CustomFeesInterface
    {
        return $this->setData(self::FIELD_ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomFeeCode(): string
    {
        return parent::getData(self::FIELD_FEE_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCustomFeeCode(string $customFeeCode): CustomFeesInterface
    {
        return $this->setData(self::FIELD_FEE_CODE, $customFeeCode);
    }

    /**
     * @inheritDoc
     */
    public function getCustomFeeLabel(): string
    {
        return parent::getData(self::FIELD_FEE_LABEL);
    }

    /**
     * @inheritDoc
     */
    public function setCustomFeeLabel(string $customFeeLabel): CustomFeesInterface
    {
        return $this->setData(self::FIELD_FEE_LABEL, $customFeeLabel);
    }

    /**
     * @inheritDoc
     */
    public function getCustomFeeAmount(): float
    {
        return (float) parent::getData(self::FIELD_FEE_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setCustomFeeAmount(float $customFeeAmount): CustomFeesInterface
    {
        return $this->setData(self::FIELD_FEE_AMOUNT, $customFeeAmount);
    }

    /**
     * @inheritDoc
     */
    public function getBaseCustomFeeAmount(): float
    {
        return (float) parent::getData(self::FIELD_BASE_FEE_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setBaseCustomFeeAmount(float $baseCustomFeeAmount): CustomFeesInterface
    {
        return $this->setData(self::FIELD_BASE_FEE_AMOUNT, $baseCustomFeeAmount);
    }

    /**
     * @inheritDoc
     */
    public function getCustomFeeAmountInvoiced(): float
    {
        return (float) parent::getData(self::FIELD_FEE_AMOUNT_INVOICED);
    }

    /**
     * @inheritDoc
     */
    public function setCustomFeeAmountInvoiced(float $customFeeAmountInvoiced): CustomFeesInterface
    {
        return $this->setData(self::FIELD_FEE_AMOUNT_INVOICED, $customFeeAmountInvoiced);
    }

    /**
     * @inheritDoc
     */
    public function getBaseCustomFeeAmountInvoiced(): float
    {
        return (float) parent::getData(self::FIELD_BASE_FEE_AMOUNT_INVOICED);
    }

    /**
     * @inheritDoc
     */
    public function setBaseCustomFeeAmountInvoiced(float $baseCustomFeeAmountInvoiced): CustomFeesInterface
    {
        return $this->setData(self::FIELD_BASE_FEE_AMOUNT_INVOICED, $baseCustomFeeAmountInvoiced);
    }

    /**
     * @inheritDoc
     */
    public function getCustomFeeAmountRefunded(): float
    {
        return (float) parent::getData(self::FIELD_FEE_AMOUNT_REFUNDED);
    }

    /**
     * @inheritDoc
     */
    public function setCustomFeeAmountRefunded(float $customFeeAmountRefunded): CustomFeesInterface
    {
        return $this->setData(self::FIELD_FEE_AMOUNT_REFUNDED, $customFeeAmountRefunded);
    }

    /**
     * @inheritDoc
     */
    public function getBaseCustomFeeAmountRefunded(): float
    {
        return (float) parent::getData(self::FIELD_BASE_FEE_AMOUNT_REFUNDED);
    }

    /**
     * @inheritDoc
     */
    public function setBaseCustomFeeAmountRefunded(float $baseCustomFeeAmountRefunded): CustomFeesInterface
    {
        return $this->setData(self::FIELD_BASE_FEE_AMOUNT_REFUNDED, $baseCustomFeeAmountRefunded);
    }

    /**
     * @inheritDoc
     */
    public function isApplyFeeOnLastInvoice(): bool
    {
        return (bool) parent::getData(self::FIELD_APPLY_FEE_ON_LAST_INVOICE);
    }

    /**
     * @inheritDoc
     */
    public function setApplyFeeOnLastInvoice(bool $applyFeeOnLastInvoice = false): CustomFeesInterface
    {
        return $this->setData(self::FIELD_APPLY_FEE_ON_LAST_INVOICE, $applyFeeOnLastInvoice);
    }

    /**
     * @inheritDoc
     */
    public function isApplyFeeOnLastCreditmemo(): bool
    {
        return (bool) parent::getData(self::FIELD_APPLY_FEE_ON_LAST_CREDITMEMO);
    }

    /**
     * @inheritDoc
     */
    public function setApplyFeeOnLastCreditmemo(bool $applyFeeOnLastCreditmemo = true): CustomFeesInterface
    {
        return $this->setData(self::FIELD_APPLY_FEE_ON_LAST_CREDITMEMO, $applyFeeOnLastCreditmemo);
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceId(): ?string
    {
        return parent::getData(self::FIELD_INVOICE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setInvoiceId(string $invoiceId): CustomFeesInterface
    {
        return $this->setData(self::FIELD_INVOICE_ID, $invoiceId);
    }

    /**
     * @inheritDoc
     */
    public function getCreditmemoId(): ?string
    {
        return parent::getData(self::FIELD_CREDITMEMO_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCreditmemoId(string $creditmemoId): CustomFeesInterface
    {
        return $this->setData(self::FIELD_CREDITMEMO_ID, $creditmemoId);
    }
}
