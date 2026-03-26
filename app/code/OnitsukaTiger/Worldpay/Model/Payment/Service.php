<?php

namespace OnitsukaTiger\Worldpay\Model\Payment;

class Service extends \Sapient\Worldpay\Model\Payment\Service
{
    /**
     * Create Payment Update From WorldPay Xml
     *
     * @param string $xml
     * @return array
     */
    public function createPaymentUpdateFromWorldPayXml($xml)
    {
        return $this->_getPaymentUpdateFactory()
            ->create(new \OnitsukaTiger\Worldpay\Model\Payment\StateXml($xml));
    }
}
