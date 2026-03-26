<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddPaymentInfoBuilderInterface
{
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $paymentType
     * @param boolean $isAdmin
     * @return null|AddPaymentInfoInterface
     */
    public function getAddPaymentInfoEvent($order, $paymentType,  $isAdmin = false);
}
