<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataServicesMultishipping\ViewModel\Checkout;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * ViewModel for Multishipping Checkout Success Context
 */
class MultishippingSuccessContextProvider implements ArgumentInterface
{
    /**
     * @param Multishipping $multishipping
     * @param CustomerSession $customerSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Json $jsonSerializer
     */
    public function __construct(
        private Multishipping $multishipping,
        private CustomerSession $customerSession,
        private OrderRepositoryInterface $orderRepository,
        private Json $jsonSerializer
    ) {
    }

    /**
     * Return cart id for event tracking
     *
     * @return int
     */
    public function getCartId(): int
    {
        $cartId = $this->customerSession->getDataServicesCartId();
        $this->customerSession->unsDataServicesCartId();
        return $cartId;
    }

    /**
     * Return customer email for event tracking
     *
     * @return string
     */
    public function getCustomerEmail(): string
    {
        return $this->customerSession->getCustomer()->getEmail();
    }

    /**
     * Return order ids for event tracking
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return implode(',', $this->multishipping->getOrderIds());
    }

    /**
     * Return sum grandTotal, taxAmount, and discountAmount for all orders generated in multishipping
     * @return array
     */
    private function getMultishippingTotals(): array
    {
        $grandTotal = 0;
        $discountAmount = 0;
        $taxAmount = 0;
        foreach ($this->multishipping->getOrderIds() as $orderId) {
            $order = $this->orderRepository->get($orderId);
            $grandTotal += $order->getGrandTotal();
            $discountAmount += $order->getDiscountAmount();
            $taxAmount += $order->getTaxAmount();
        }
        return [
            'grandTotal' => round((float)$grandTotal, 2),
            'discountAmount' => round((float)$discountAmount, 2),
            'taxAmount' => round((float)$taxAmount, 2),
        ];
    }

    /**
     * Return payment method data.
     * Tmp. return data only for the latest order
     *
     * @return array
     */
    public function getPayment(): array
    {
        $paymentData = [];
        foreach ($this->multishipping->getOrderIds() as $orderId) {
            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();
            $paymentData[] = [
                'total' => round((float)$payment->getAmountOrdered(), 2),
                'paymentMethodCode' => $payment->getMethod(),
                'paymentMethodName' => $payment->getMethodInstance()->getTitle(),
                'orderId' => $orderId,
            ];
        }
        return $paymentData;
    }

    /**
     * Return shipping data
     *
     * @return array
     */
    public function getShipping(): array
    {
        $shippingData = [];
        foreach ($this->multishipping->getOrderIds() as $orderId) {
            $order = $this->orderRepository->get($orderId);
            $shippingData[$orderId] =
                [
                    'shippingMethod' => $order->getShippingMethod(),
                    'shippingAmount' => $order->getShippingAmount(),
                ];
        }
        return $shippingData;
    }

    /**
     * Return discount amount
     * Currently going to return the sum of all discounts
     * In future if multiple events are sent per order, this should be changed to return the discount for each order
     * @return float
     */
    public function getDiscountAmount(): float
    {
        $discountData = 0;
        foreach ($this->multishipping->getOrderIds() as $orderId) {
            $order = $this->orderRepository->get($orderId);
            $discountData += $order->getDiscountAmount();
        }
        return $discountData;
    }

    /**
     * Return order context for event tracking
     *
     * @return string
     */
    public function getOrderContext(): string 
    {
        $orderTotals = $this->getMultishippingTotals();
        $orderContext = [
            'orderId' => $this->getOrderId(),
            'payments' => $this->getPayment(),
            'shipping' => $this->getShipping(),
            'discountAmount' => $orderTotals['discountAmount'],
            'taxAmount' => $orderTotals['taxAmount'],
            'grandTotal' => $orderTotals['grandTotal'],
        ];

        return $this->jsonSerializer->serialize($orderContext);
    }
}
