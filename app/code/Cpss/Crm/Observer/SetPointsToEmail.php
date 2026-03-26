<?php

namespace Cpss\Crm\Observer;

use Magento\Framework\Event\ObserverInterface;

class SetPointsToEmail implements ObserverInterface
{
    protected $session;

    public function __construct(
        \Magento\Checkout\Model\Session $session
    )
    {
        $this->session = $session;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getTransport();
        $order = $transport->getOrder();
        $transport['applied_points'] = $order->getUsedPoint() ? number_format($order->getUsedPoint()) :0;
        $transport['earned_points'] = $order->getAcquiredPoint() ? number_format($order->getAcquiredPoint()) :0;
    }
}