<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Service\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Vaimo\AepBase\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Api\AepMapperInterface;

class AepMapper implements AepMapperInterface
{
    private StoreManagerInterface $storeManager;

    /**
     * @var Vaimo\AepEventStreaming\Helper\Data
     */
    protected $helper;

    /**
     * @param Vaimo\AepEventStreaming\Helper\Data $helper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        \Vaimo\AepEventStreaming\Helper\Data $helper
    ) {
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function map(OrderInterface $order): array
    {
        $shippingAddress = $order->getShippingAddress();
        /*$writer = new \Zend_Log_Writer_Stream(BP . "/var/log/aep/order_sync_data.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("==== AepMapper order store Id ====");
        $logger->info($order->getStoreId());*/
        // $storeCodes = $this->helper->getExcludeStoreStreaming();

        $orderStoreCode =  $this->helper->getStoreCodeById($order->getStoreId());
        $storeCodes = $this->helper->getExcludeStoreStreaming();

        /*$logger->info("==== AepMapper order Store Code mapper====");
        $logger->info(print_r($orderStoreCode, true));

        $logger->info("==== AepMapper config store Codes mapper ====");
        $logger->info(print_r($storeCodes, true));

        $logger->info("=============");*/

        $storeCodesArray = [];
        if (!empty($storeCodes)) {
            $storeCodesArray = explode(',', $storeCodes);
        }

        /*$logger->info("==== AepMapper exploded order storeCodesArray ====");
        $logger->info(print_r($storeCodesArray, true));*/

        /*$logger->info("==== AepMapper check order condition if not in_array ====");
        if (!in_array($orderStoreCode, $storeCodesArray)) {
             $logger->info("==== called if  ====");
        }else{
            $logger->info("==== called else ====");
        }*/

        $orderDataArr = [];
        if (!in_array($orderStoreCode, $storeCodesArray)) {
            if (!$shippingAddress) {
                $shippingAddress = $order->getBillingAddress();
            }

            $giftCardAmt = 0;

            // gift cards module is disabled ATM, this is to avoid problem when module will be re-enabled
            if (\method_exists($order->getExtensionAttributes(), 'getGiftCardsAmount')) {
                $giftCardAmt = $order->getExtensionAttributes()->getGiftCardsAmount();
            }

            $store = $this->getStoreCode($order);

            $orderDataArr = [
                'customerId' => $order->getCustomerId(),
                'billingAddress' => $this->mapAddress($order->getBillingAddress()),
                'couponCode' => $order->getCouponCode(),
                'currency' => [
                    'currencyCode' => $order->getOrderCurrencyCode(),
                ],
                'discountAmt' => $order->getDiscountAmount(),
                'giftCardAmt' => $giftCardAmt,
                'grandTotalAmt' => $order->getGrandTotal(),
                'orderItemCnt' => $order->getTotalItemCount(),
                'orderCreatedDate' => $this->convertDateTimeFormat($order->getCreatedAt()),
                'orderId' => $order->getIncrementId(),
                'orderItems' => $this->getOrderItems($order),
                'orderStatus' => $order->getStatus(),
                'orderStatusChangeDate' => $this->getLastOrderStatusChangeDate($order),
                'shippingAddress' => $this->mapAddress($shippingAddress),
                'shippingAmt' => $order->getShippingAmount(),
                'store' => [
                    'storeId' => $this->getStoreCodeById($order->getStoreId()),
                ],
                'subTotalAmt' => $order->getSubtotalInclTax(),
                'taxAmt' => $order->getTaxAmount(),
                'daysSincePreviousOrder' => $this->getDaysSinceLastOrder($order),
            ];

            /*$logger->info("==== aep mapper order Data =======");
            $logger->info(print_r($orderDataArr, true));*/
        }

        return $orderDataArr;
    }

    private function getStoreCodeById($storeId)
    {
        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $store->getCode();
    }
    private function getLastOrderStatusChangeDate(OrderInterface $order): string
    {
        $result = \DateTime::createFromFormat('Y-m-d H:i:s', $order->getCreatedAt());

        foreach ($order->getStatusHistories() as $statusHistory) {
            if ($statusHistory->getCreatedAt() === null) {
                $newCandidate = new \DateTime(); // new status which was just saved to db
            } else {
                $newCandidate = \DateTime::createFromFormat('Y-m-d H:i:s', $statusHistory->getCreatedAt());
            }

            // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
            if ($newCandidate > $result) {
                $result = $newCandidate;
            }
        }

        return $result->format(self::AEP_DATETIME_FORMAT);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function mapAddress(?OrderAddressInterface $address): array
    {
        if ($address === null) {
            return [];
        }
        
        return [
            'city' => $address->getCity(),
            'country' => $address->getCountryId(),
            'postCode' => $address->getPostcode(),
            'region' => ($address->getRegion() ? $address->getRegion() : null),
            'street' => is_array($address->getStreet()) ? implode(", ", $address->getStreet()) : null,
        ];
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function getOrderItems(OrderInterface $order): array
    {
        $result = [];
        $parentsIds = [];
        $storeCode = $this->getStoreCode($order);
        $storeCodeSuffix = '';

        if ($storeCode) {
            $storeCodeSuffix = ConfigInterface::STORE_CODE_DELIMITER . $storeCode;
        }

        $this->reloadItems($order);

        foreach ($order->getItems() as $item) {
            $parentsIds[] = $item->getParentItemId();
            $itemPrice = $item->getPriceInclTax();
            $totalItemPrice = $item->getRowTotalInclTax();

            if ($itemPrice === null && $item->getParentItem()) {
                $itemPrice = $item->getParentItem()->getPriceInclTax();
                $totalItemPrice = $item->getParentItem()->getRowTotalInclTax();
            }

            $result[$item->getItemId()] = [
                'itemPrice' => $itemPrice,
                'orderItemId' => $item->getItemId(),
                'orderLineStatus' => null,
                'parentOrderItemId' => $item->getParentItemId(),
                'quantity' => $item->getQtyOrdered(),
                'skuStoreViewCode' => $item->getSku() . $storeCodeSuffix,
                'strOrderLineStatusChangeDate' => null,
                'totalItemPrice' => $totalItemPrice,
            ];
        }

        return \array_values($this->dehydrateParents($result, $parentsIds));
    }

    /**
     * @param string[][] $items
     * @param int[] $parentIds
     * @return string[][]
     */
    private function dehydrateParents(array $items, array $parentIds): array
    {
        foreach ($parentIds as $parentId) {
            if (!isset($items[$parentId])) {
                continue;
            }

            $items[$parentId]['itemPrice'] = null;
            $items[$parentId]['quantity'] = null;
            $items[$parentId]['totalItemPrice'] = null;
        }

        return $items;
    }

    private function getDaysSinceLastOrder(OrderInterface $order): ?int
    {
        $currentOrderDate = \DateTime::createFromFormat(
            DateTime::DATETIME_PHP_FORMAT,
            $order->getCreatedAt()
        );
        $previousOrderDate = \DateTime::createFromFormat(
            DateTime::DATE_PHP_FORMAT,
            (string) $order->getExtensionAttributes()->getCustomerPreviousOrderDate()
        );

        if (!$currentOrderDate || !$previousOrderDate) {
            return null;
        }

        $dateDiff = $currentOrderDate->diff($previousOrderDate);

        return $dateDiff->d;
    }

    /**
     * Newly created order does not have order items ids, we need to force magento to reload them
     */
    private function reloadItems(OrderInterface $order): void
    {
        $items = $order->getItems();
        $item = \reset($items);

        if ($item->getItemId()) {
            return;
        }

        $order->setItems(null);
    }

    private function getStoreCode(OrderInterface $order): ?string
    {
        try {
            $store = $this->storeManager->getStore($order->getStoreId());
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $store->getCode();
    }

    private function convertDateTimeFormat(?string $dateTimeString): ?string
    {
        if ($dateTimeString === null) {
            return null;
        }

        return \DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeString)
            ->format(self::AEP_DATETIME_FORMAT);
    }
}
