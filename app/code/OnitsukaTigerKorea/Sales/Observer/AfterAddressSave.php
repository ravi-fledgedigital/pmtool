<?php

namespace OnitsukaTigerKorea\Sales\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * for save the shipping telephone and billing telephone, 'admin_sales_order_address_update' event
 */
class AfterAddressSave implements ObserverInterface
{

    /**
     * @var $orderRepository
     */
    protected $orderRepository;

    /**
     * AfterAddressSave constructor.
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $orderId = $observer->getEvent()->getOrderId();
        $order = $this->orderRepository->get($orderId);
        $shippingPhone = $order->getShippingAddress()->getTelephone();
        $billingPhone = $order->getBillingAddress()->getTelephone();
        $order->setData('billing_telephone', $billingPhone);
        $order->setData('shipping_telephone', $shippingPhone);

        if ($order->getOrigData('billing_telephone') != $order->getData('billing_telephone') ||
            $order->getOrigData('shipping_telephone') != $order->getData('shipping_telephone')
        ) {
            $order->save();
        }
    }
}

