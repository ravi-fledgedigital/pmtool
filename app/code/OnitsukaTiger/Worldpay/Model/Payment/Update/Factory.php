<?php

namespace OnitsukaTiger\Worldpay\Model\Payment\Update;

use Sapient\Worldpay\Helper\Data;
use Sapient\Worldpay\Model\Payment\StateInterface;
use Sapient\Worldpay\Model\Payment\Update\Authorised;
use Sapient\Worldpay\Model\Payment\Update\Cancelled;
use Sapient\Worldpay\Model\Payment\Update\Captured;
use Sapient\Worldpay\Model\Payment\Update\Defaultupdate;
use Sapient\Worldpay\Model\Payment\Update\Error;
use Sapient\Worldpay\Model\Payment\Update\PendingPayment;
use Sapient\Worldpay\Model\Payment\Update\Refunded;
use Sapient\Worldpay\Model\Payment\Update\RefundFailed;
use Sapient\Worldpay\Model\Payment\Update\SentForRefund;
use Sapient\Worldpay\Model\Payment\WorldPayPayment;

class Factory extends \Sapient\Worldpay\Model\Payment\Update\Factory
{
    private Data $_configHelper;

    protected $_multishippingHelper;

    /**
     * @var \Sapient\Worldpay\Model\Payment\WorldPayPayment
     */
    private $worldpaymentmodel;

    public function __construct(
        Data $configHelper,
        WorldPayPayment $worldpaymentmodel,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
    ) {
        $this->_configHelper = $configHelper;
        $this->worldpaymentmodel = $worldpaymentmodel;
        $this->_multishippingHelper = $multishippingHelper;
        parent::__construct($configHelper, $worldpaymentmodel, $multishippingHelper);
    }

    /**
     * Create class instance with specified parameters
     *
     * @param StateInterface $paymentState
     * @return object
     */
    public function create(StateInterface $paymentState)
    {
        switch ($paymentState->getPaymentStatus()) {
            case StateInterface::STATUS_AUTHORISED:
                return new Authorised($paymentState, $this->worldpaymentmodel, $this->_configHelper, $this->_multishippingHelper);

            case StateInterface::STATUS_CAPTURED:
                return new Captured($paymentState, $this->worldpaymentmodel, $this->_configHelper);

            case StateInterface::STATUS_SENT_FOR_REFUND:
                return new SentForRefund($paymentState, $this->worldpaymentmodel, $this->_configHelper);

            case StateInterface::STATUS_REFUNDED:
                return new Refunded($paymentState, $this->worldpaymentmodel, $this->_configHelper);

            case StateInterface::STATUS_REFUND_EXPIRED:
            case StateInterface::STATUS_REFUND_FAILED:
                return new RefundFailed($paymentState, $this->worldpaymentmodel, $this->_configHelper);

            case StateInterface::STATUS_CANCELLED:
                return new Cancelled($paymentState, $this->worldpaymentmodel, $this->_configHelper);

            case StateInterface::STATUS_REFUSED:
                if ($paymentState->isRefusedEvent()) {
                    return new Refused($paymentState, $this->worldpaymentmodel, $this->_configHelper, $this->_multishippingHelper);
                } else {
                    return new Defaultupdate($paymentState, $this->worldpaymentmodel, $this->_configHelper, $this->_multishippingHelper);
                }

            case StateInterface::STATUS_ERROR:
                return new Error($paymentState, $this->worldpaymentmodel, $this->_configHelper, $this->_multishippingHelper);

            case StateInterface::STATUS_PENDING_PAYMENT:
                return new PendingPayment($paymentState, $this->worldpaymentmodel, $this->_configHelper, $this->_multishippingHelper);

            default:
                return new Defaultupdate($paymentState, $this->worldpaymentmodel, $this->_configHelper, $this->_multishippingHelper);
        }
    }
}
