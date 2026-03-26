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

namespace Magento\AdminUiSdkCustomFees\Api;

use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\AdminUiSdkCustomFees\Model\ResourceModel\SalesOrder\Collection;

/**
 * Interface CustomFeesRepositoryInterface
 *
 * @api
 */
interface CustomFeesRepositoryInterface
{
    /**
     * Load custom sales order by id.
     *
     * @param int $id
     * @return CustomFeesInterface
     */
    public function getById(int $id): CustomFeesInterface;

    /**
     * Save custom sales order.
     *
     * @param CustomFeesInterface $salesOrder
     * @return void
     */
    public function save(CustomFeesInterface $salesOrder): void;

    /**
     * Load custom sales order by order id.
     *
     * @param string $orderId
     * @return array|Collection
     */
    public function getByOrderId(string $orderId);

    /**
     * Load custom fees order by invoice id.
     *
     * @param string $invoiceId
     * @return array
     */
    public function getByInvoiceId(string $invoiceId);

    /**
     * Load custom fees order by credit memo id.
     *
     * @param string $creditMemoId
     * @return array
     */
    public function getByCreditMemoId(string $creditMemoId);

    /**
     * Add an invoiced amount to an existing custom sales order.
     *
     * @param int $id
     * @param string $invoiceId
     * @param float $amount
     * @param float $baseAmount
     * @return void
     */
    public function addInvoicedAmount(int $id, string $invoiceId, float $amount, float $baseAmount): void;

    /**
     * Add a refunded amount to an existing custom sales order.
     *
     * @param int $id
     * @param float $amount
     * @param float $baseAmount
     * @param string $creditMemoId
     * @return void
     */
    public function addRefundedAmountAndCreditMemoId(
        int $id,
        float $amount,
        float $baseAmount,
        string $creditMemoId
    ): void;
}
