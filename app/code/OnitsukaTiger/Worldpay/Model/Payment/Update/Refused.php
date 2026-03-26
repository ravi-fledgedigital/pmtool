<?php
/**
 * @copyright 2017 Sapient
 */
namespace OnitsukaTiger\Worldpay\Model\Payment\Update;

class Refused extends \Sapient\Worldpay\Model\Payment\Update\Refused
{
    public function apply($payment, $order = null)
    {
        if (!empty($order)) {
            $this->_assertValidPaymentStatusTransition($order, $this->_getAllowedPaymentStatuses());
            $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
            $order->cancel();
        }
    }

    /**
     * Get allow payment status
     *
     * @return array
     */
    protected function _getAllowedPaymentStatuses()
    {
        return [
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_AUTHORISATION,
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_AUTHORISED,
            \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUSED,
        ];
    }
}
