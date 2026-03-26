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

namespace Magento\AdminUiSdkCustomFees\Api\Data;

/**
 * Interface CustomFeesInterface
 *
 * @api
 */
interface CustomFeesInterface
{
    public const FIELD_ENTITY_ID = 'entity_id';
    public const FIELD_ORDER_ID = 'order_id';
    public const FIELD_FEE_CODE = 'custom_fee_code';
    public const FIELD_FEE_LABEL = 'custom_fee_label';
    public const FIELD_FEE_AMOUNT = 'custom_fee_amount';
    public const FIELD_BASE_FEE_AMOUNT = 'base_custom_fee_amount';
    public const FIELD_FEE_AMOUNT_INVOICED = 'custom_fee_amount_invoiced';
    public const FIELD_BASE_FEE_AMOUNT_INVOICED = 'base_custom_fee_amount_invoiced';
    public const FIELD_FEE_AMOUNT_REFUNDED = 'custom_fee_amount_refunded';
    public const FIELD_BASE_FEE_AMOUNT_REFUNDED = 'base_custom_fee_amount_refunded';
    public const FIELD_APPLY_FEE_ON_LAST_INVOICE = 'apply_fee_on_last_invoice';
    public const FIELD_APPLY_FEE_ON_LAST_CREDITMEMO = 'apply_fee_on_last_creditmemo';
    public const FIELD_INVOICE_ID = 'invoice_id';
    public const FIELD_CREDITMEMO_ID = 'creditmemo_id';

    /**
     * Returns id.
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Returns the order id
     *
     * @return string
     */
    public function getOrderId(): string;

    /**
     * Sets order id
     *
     * @param string $orderId
     * @return CustomFeesInterface
     */
    public function setOrderId(string $orderId): CustomFeesInterface;

    /**
     * Returns custom fee code.
     *
     * @return string
     */
    public function getCustomFeeCode(): string;

    /**
     * Sets custom fee code.
     *
     * @param string $customFeeCode
     * @return CustomFeesInterface
     */
    public function setCustomFeeCode(string $customFeeCode): CustomFeesInterface;

    /**
     * Returns custom fee label.
     *
     * @return string
     */
    public function getCustomFeeLabel(): string;

    /**
     * Sets custom fee label.
     *
     * @param string $customFeeLabel
     * @return CustomFeesInterface
     */
    public function setCustomFeeLabel(string $customFeeLabel): CustomFeesInterface;

    /**
     * Returns custom fee amount.
     *
     * @return float
     */
    public function getCustomFeeAmount(): float;

    /**
     * Sets custom fee amount.
     *
     * @param float $customFeeAmount
     * @return CustomFeesInterface
     */
    public function setCustomFeeAmount(float $customFeeAmount): CustomFeesInterface;

    /**
     * Returns base custom fee amount.
     *
     * @return float
     */
    public function getBaseCustomFeeAmount(): float;

    /**
     * Sets base custom fee amount.
     *
     * @param float $baseCustomFeeAmount
     * @return CustomFeesInterface
     */
    public function setBaseCustomFeeAmount(float $baseCustomFeeAmount): CustomFeesInterface;

    /**
     * Returns custom fee amount invoiced.
     *
     * @return float
     */
    public function getCustomFeeAmountInvoiced(): float;

    /**
     * Sets custom fee amount invoiced.
     *
     * @param float $customFeeAmountInvoiced
     * @return CustomFeesInterface
     */
    public function setCustomFeeAmountInvoiced(float $customFeeAmountInvoiced): CustomFeesInterface;

    /**
     * Returns base custom fee amount invoiced.
     *
     * @return float
     */
    public function getBaseCustomFeeAmountInvoiced(): float;

    /**
     * Sets base custom fee amount invoiced.
     *
     * @param float $baseCustomFeeAmountInvoiced
     * @return CustomFeesInterface
     */
    public function setBaseCustomFeeAmountInvoiced(float $baseCustomFeeAmountInvoiced): CustomFeesInterface;

    /**
     * Returns custom fee amount refunded.
     *
     * @return float
     */
    public function getCustomFeeAmountRefunded(): float;

    /**
     * Sets custom fee amount refunded.
     *
     * @param float $customFeeAmountRefunded
     * @return CustomFeesInterface
     */
    public function setCustomFeeAmountRefunded(float $customFeeAmountRefunded): CustomFeesInterface;

    /**
     * Returns base custom fee amount refunded.
     *
     * @return float
     */
    public function getBaseCustomFeeAmountRefunded(): float;

    /**
     * Sets base custom fee amount refunded.
     *
     * @param float $baseCustomFeeAmountRefunded
     * @return CustomFeesInterface
     */
    public function setBaseCustomFeeAmountRefunded(float $baseCustomFeeAmountRefunded): CustomFeesInterface;

    /**
     * Returns if apply fee is set on last invoice.
     *
     * @return bool
     */
    public function isApplyFeeOnLastInvoice(): bool;

    /**
     * Sets if apply fee is on last invoice.
     *
     * @param bool $applyFeeOnLastInvoice
     * @return CustomFeesInterface
     */
    public function setApplyFeeOnLastInvoice(bool $applyFeeOnLastInvoice): CustomFeesInterface;

    /**
     * Returns if apply fee is set on last credit memo.
     *
     * @return bool
     */
    public function isApplyFeeOnLastCreditmemo(): bool;

    /**
     * Sets if apply fee is on last credit memo.
     *
     * @param bool $applyFeeOnLastCreditmemo
     * @return CustomFeesInterface
     */
    public function setApplyFeeOnLastCreditmemo(bool $applyFeeOnLastCreditmemo): CustomFeesInterface;

    /**
     * Returns invoice id.
     *
     * @return string|null
     */
    public function getInvoiceId(): ?string;

    /**
     * Sets invoice id.
     *
     * @param string $invoiceId
     * @return CustomFeesInterface
     */
    public function setInvoiceId(string $invoiceId): CustomFeesInterface;

    /**
     * Returns credit memo id.
     *
     * @return string|null
     */
    public function getCreditmemoId(): ?string;

    /**
     * Sets credit memo.
     *
     * @param string $creditmemoId
     * @return CustomFeesInterface
     */
    public function setCreditmemoId(string $creditmemoId): CustomFeesInterface;
}
