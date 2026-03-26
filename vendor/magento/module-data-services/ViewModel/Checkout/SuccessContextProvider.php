<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataServices\ViewModel\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * ViewModel for Checkout Success Context
 */
class SuccessContextProvider implements ArgumentInterface
{
    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var Json
     */
    private Json $jsonSerializer;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Json $jsonSerializer
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Json $jsonSerializer,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->jsonSerializer = $jsonSerializer;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Return cart id for event tracking
     *
     * @return int
     */
    public function getCartId(): int
    {
        return (int)$this->checkoutSession->getLastRealOrder()->getQuoteId();
    }

    /**
     * Return customer email for event tracking
     *
     * @return string
     */
    public function getCustomerEmail(): string
    {
        return (string)$this->checkoutSession->getLastRealOrder()->getCustomerEmail();
    }

    /**
     * Return payment method data
     *
     * @return array
     */
    public function getPayment(): array
    {
        $order = $this->checkoutSession->getLastRealOrder();

        $payment = $order->getPayment();
        // Payment might not exist for async placed orders.
        if ($payment) {
            $paymentData['total'] = round((float)$payment->getAmountOrdered(), 2);
            $paymentData['paymentMethodCode'] = $payment->getMethod();
            $paymentData['paymentMethodName'] = $payment->getMethodInstance()->getTitle();
        }

        return [$paymentData];
    }

    /**
     * Return shipping data
     *
     * @return array
     */
    public function getShipping(): array
    {
        return [
            'shippingMethod' => $this->checkoutSession->getLastRealOrder()->getShippingMethod(),
            'shippingAmount' => round((float)$this->checkoutSession->getLastRealOrder()->getShippingAmount(), 2),
        ];
    }

    /**
     * Return order context for event tracking
     *
     * @return string
     */
    public function getOrderContext(): string
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $orderContext = [
            'orderId' => (string)$order->getIncrementId(),
            'payments' => $this->getPayment(),
            'shipping' => $this->getShipping(),
            'discountAmount' => round((float)$order->getDiscountAmount(), 2),
            'grandTotal' => round((float)$order->getGrandTotal(), 2),
            'taxAmount' => round((float)$order->getTaxAmount(), 2),
        ];

        return $this->jsonSerializer->serialize($orderContext);
    }
}
