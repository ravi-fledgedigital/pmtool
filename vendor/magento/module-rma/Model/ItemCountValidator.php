<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\Rma\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\AbstractValidator;
use Magento\Rma\Helper\Data as RmaHelper;
use Magento\Rma\Model\ResourceModel\ItemFactory;
use Magento\Framework\Escaper;
use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Model\Rma\Source\Status;

class ItemCountValidator extends AbstractValidator
{
    /**
     * @var RmaHelper
     */
    private RmaHelper $dataHelper;

    /**
     * @var ItemFactory
     */
    private ItemFactory $itemFactory;

    /**
     * @var Escaper
     */
    private Escaper $escaper;

    /**
     * @param RmaHelper $dataHelper
     * @param ItemFactory $itemFactory
     * @param Escaper $escaper
     */
    public function __construct(RmaHelper $dataHelper, ItemFactory $itemFactory, Escaper $escaper)
    {
        $this->dataHelper = $dataHelper;
        $this->itemFactory = $itemFactory;
        $this->escaper = $escaper;
    }

    /**
     * Validate Return items quantity
     *
     * @param Rma $value
     * @return bool
     * @throws LocalizedException
     */
    public function isValid($value)
    {
        if ($value->getStatus() == Status::STATE_CLOSED) {
            return true;
        }

        if ($value->getIsUpdate() || $value->getEntityId()) {
            $items = $value->getItems();
            foreach ($items as $item) {
                $this->checkQuantities($item);
                $this->checkQuantityStatuses($item);
            }
        } else {
            if (false === in_array($value->getStatus(), Status::STATE_ALL)) {
                $this->_addMessages([__('Invalid status provided: %1', $value->getStatus())]);
                return false;
            }
        }

        $this->checkAvailability($value);

        if (!empty($this->getMessages())) {
            return false;
        }

        return true;
    }

    /**
     * Checks Rma items against initial order items
     *
     * @param Rma $value
     * @return void
     * @throws LocalizedException
     */
    private function checkAvailability(Rma $value): void
    {
        $errors = $errorKeys = [];
        $availableItemsArray = $this->getAvailableItems($value);
        $itemsArray = $this->getItemsTotals($value);
        foreach ($itemsArray as $key => $info) {
            if (!array_key_exists($key, $availableItemsArray)) {
                $errors['return_item_not_allowed'] = __('You cannot return %1.', $key);
                continue;
            }
            if ($availableItemsArray[$key]['qty'] < $info['quantity']) {
                $escapedProductName = $this->escaper->escapeHtml($availableItemsArray[$key]['name']);
                $errors['return_item_quantity_not_allowed'] =
                    __('A quantity of %1 is greater than you can return.', $escapedProductName);
                $errorKeys[$key] = 'qty_requested';
                $errorKeys['tabs'] = 'items_section';
            }
        }

        if ($errors || $errorKeys) {
            $this->_addMessages(array_merge($errors, ['error_keys' => $errorKeys]));
        }
    }

    /**
     * Extract total quantities
     *
     * @param Rma $value
     * @return array
     */
    private function getItemsTotals(Rma $value): array
    {
        $itemsArray = [];
        $items = $value->getItems();
        foreach ($items as $item) {
            if (!isset($itemsArray[$item->getOrderItemId()])) {
                $itemsArray[$item->getOrderItemId()]['quantity'] = $item->getQtyRequested();
            } else {
                $itemsArray[$item->getOrderItemId()]['quantity'] += $item->getQtyRequested();
            }
            $itemsArray[$item->getOrderItemId()]['status'] = $item->getStatus();
        }
        ksort($itemsArray);

        return $itemsArray;
    }

    /**
     * Validate item quantity status
     *
     * @param ItemInterface $item
     * @return void
     */
    private function checkQuantityStatuses(ItemInterface $item): void
    {
        $errors = $errorKeys = [];
        $escapedProductName = $this->escaper->escapeHtml($item->getProductName());

        //if we change item status i.e. to authorized, then qty_authorized must be non-empty and so on.
        foreach ($this->getQtyToStatus() as $qtyKey => $qtyValue) {
            if ($item->getStatus() === $qtyValue['status']
                && $item->getOrigData('status') !== $qtyValue['status']
                && !$item->getData($qtyKey)
            ) {
                $errors[] = __('%1 for item %2 cannot be empty.', $qtyValue['name'], $escapedProductName);
                $errorKeys[$item->getId()] = $qtyKey;
                $errorKeys['tabs'] = 'items_section';
            }
        }

        if ($errors || $errorKeys) {
            $this->_addMessages(array_merge($errors, ['error_keys' => $errorKeys]));
        }
    }

    /**
     * Check if there are enough items in the order to perform a return
     *
     * @param ItemInterface $item
     * @return void
     */
    private function checkQuantities(ItemInterface $item): void
    {
        $validation = $errors = $errorKeys = [];
        foreach ([Rma::QTY_REQUESTED, Rma::QTY_AUTHORIZED, Rma::QTY_RETURNED, Rma::QTY_APPROVED] as $tempQty) {
            $quantity = $item->getData($tempQty);
            if ($quantity === null) {
                if ($item->getOrigData($tempQty) !== null) {
                    $validation[$tempQty] = (double)$item->getOrigData($tempQty);
                }
            } else {
                $validation[$tempQty] = (double)$quantity;
            }
        }
        $validation['dummy'] = -1;
        $previousValue = null;
        $escapedProductName = $this->escaper->escapeHtml($item->getProductName());
        foreach ($validation as $key => $val) {
            if (isset($previousValue) && $val > $previousValue) {
                $errors[] = __('There is an error in quantities for item %1.', $escapedProductName);
                $errorKeys[$item->getId()] = $key;
                $errorKeys['tabs'] = 'items_section';
                break;
            }
            $previousValue = $val;
        }

        if ($errors || $errorKeys) {
            $this->_addMessages(array_merge($errors, ['error_keys' => $errorKeys]));
        }
    }

    /**
     * Extract all order items
     *
     * @param Rma $value
     * @return array
     * @throws LocalizedException
     */
    private function getAvailableItems(Rma $value): array
    {
        $order = $value->getOrder();
        if (!$value->getEntityId()) {
            $availableItems = $this->dataHelper->getOrderItems($order->getId())->getItems();
        } else {
            $itemResource = $this->itemFactory->create();
            $availableItems = $itemResource->getOrderItemsCollection($order->getId());
        }

        $availableItemsArray = [];
        foreach ($availableItems as $item) {
            $availableItemsArray[$item->getId()] = [
                'name' => $item->getName(),
                'qty' => $item->getAvailableQty()
            ];
        }

        return $availableItemsArray;
    }

    /**
     * Get relevant Rma statuses
     *
     * @return array[]
     */
    private function getQtyToStatus(): array
    {
        return [
            'qty_authorized' => [
                'name' => __('Authorized Qty'),
                'status' => Status::STATE_AUTHORIZED,
            ],
            'qty_returned' => [
                'name' => __('Returned Qty'),
                'status' => Status::STATE_RECEIVED,
            ],
            'qty_approved' => [
                'name' => __('Approved Qty'),
                'status' => Status::STATE_APPROVED,
            ],
        ];
    }
}
