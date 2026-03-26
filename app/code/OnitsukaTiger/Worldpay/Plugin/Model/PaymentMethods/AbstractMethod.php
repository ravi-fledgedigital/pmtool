<?php

namespace OnitsukaTiger\Worldpay\Plugin\Model\PaymentMethods;

use Exception;
use Magento\Payment\Model\InfoInterface;

class AbstractMethod
{
    /**
     * @var array
     */
    protected static $paymentDetails;

    /**
     * @var array
     */
    protected $paymentdetailsdata;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worlpayhelper;

    /**
     * @var \Sapient\Worldpay\Model\WorldpaymentFactory
     */
    protected $worldpaypayment;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    private $wplogger;

    /**
     * @var \Sapient\Worldpay\Model\Payment\PaymentTypes
     */
    private $paymenttypes;

    protected $quoteRepository;

    protected $authSession;

    protected $adminsessionquote;

    /**
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\PaymentTypes $paymenttypes
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Model\WorldpaymentFactory $worldpaypayment,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\PaymentTypes $paymenttypes,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Backend\Model\Session\Quote $adminsessionquote
    ) {
        $this->worlpayhelper = $worldpayhelper;
        $this->worldpaypayment = $worldpaypayment;
        $this->wplogger = $wplogger;
        $this->paymenttypes = $paymenttypes;
        $this->quoteRepository = $quoteRepository;
        $this->authSession = $authSession;
        $this->adminsessionquote = $adminsessionquote;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundAuthorize(
        \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod $subject,
        callable $proceed,
        InfoInterface $payment,
                                                              $amount
    ) {
        $mageOrder = $payment->getOrder();
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        if ($this->authSession->isLoggedIn()) {
            $adminquote = $this->adminsessionquote->getQuote();
            if (empty($quote->getReservedOrderId()) && !empty($adminquote->getReservedOrderId())) {
                $quote = $adminquote;
            }
        }

        $orderCode = $this->_generateOrderCode($quote);

        $this->authSession->setCurrencyCode($quote->getQuoteCurrencyCode());
        $this->paymentdetailsdata = self::$paymentDetails;
        try {
            $subject->validatePaymentData(self::$paymentDetails);
            if (self::$paymentDetails['method'] != $subject::WORLDPAY_WALLETS_TYPE) {
                $this->_checkpaymentapplicable($quote);
            }
            //$subject->_checkShippingApplicable($quote);
            $this->_createWorldPayPayment($subject, $payment, $orderCode, $quote->getStoreId(), $quote->getReservedOrderId());

            $authorisationService = $subject->getAuthorisationService($quote->getStoreId());
            $authorisationService->authorizePayment(
                $mageOrder,
                $quote,
                $orderCode,
                $quote->getStoreId(),
                self::$paymentDetails,
                $payment
            );
            $this->authSession->setOrderCode($orderCode);
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            $this->wplogger->error('Authorising payment failed.');
            $errormessage = $this->worlpayhelper->updateErrorMessage($e->getMessage(), $quote->getReservedOrderId());
            $this->wplogger->error($errormessage);
            $this->authSession->setOrderCode(false);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errormessage)
            );
        }
    }

    /**
     * @param $subject
     * @param $data
     * @return mixed
     */
    public function beforeAssignData($subject, $data)
    {
        self::$paymentDetails = $data->getData();
        return [$data];
    }

    /**
     * @return string
     */
    private function _generateOrderCode($quote)
    {
        return $quote->getReservedOrderId();
    }

    /**
     * Save Risk gardian
     */
    private function _createWorldPayPayment(
        \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod $subject,
        \Magento\Payment\Model\InfoInterface $payment,
                                                              $orderCode,
                                                              $storeId,
                                                              $orderId,
                                                              $interactionType = 'ECOM'
    ) {
        $paymentdetails = self::$paymentDetails;
        $integrationType = $this->worlpayhelper->getIntegrationModelByPaymentMethodCode($payment->getMethod(), $storeId);
        if ($paymentdetails['method'] == $subject::WORLDPAY_WALLETS_TYPE) {
            $integrationType = 'direct';
        }
        if ($paymentdetails['additional_data']['cc_type'] === 'ACH_DIRECT_DEBIT-SSL') {
            $integrationType = 'direct';
        }
        $mode = $this->worlpayhelper->getCcIntegrationMode();
        $method = $paymentdetails['method'];
        if (($mode == 'redirect') && $method == $subject::WORLDPAY_MOTO_TYPE) {
            $integrationType = 'redirect';
        }
        $wpp = $this->worldpaypayment->create();
        $wpp->setData('order_id', $orderId);
        $wpp->setData('payment_status', \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_AUTHORISATION);
        $wpp->setData('worldpay_order_id', $orderCode);
        $wpp->setData('store_id', $storeId);
        $wpp->setData(
            'merchant_id',
            $this->worlpayhelper->getMerchantCode($paymentdetails['additional_data']['cc_type'])
        );
        $wpp->setData('3d_verified', $this->worlpayhelper->isDynamic3DEnabled());
        $wpp->setData('payment_model', $integrationType);
        if ($paymentdetails && !empty($paymentdetails['additional_data']['cc_type'])
            && empty($paymentdetails['additional_data']['tokenCode'])) {
            $wpp->setData('payment_type', $paymentdetails['additional_data']['cc_type']);
        } else {
            $wpp->setData('payment_type', $this->_getpaymentType());
        }
        if ($paymentdetails['method'] == $subject::WORLDPAY_MOTO_TYPE) {
            $interactionType = 'MOTO';
        }
        if ($integrationType == $subject::DIRECT_MODEL && $this->worlpayhelper->isCseEnabled()) {
            $wpp->setData('client_side_encryption', true);
        }
        $wpp->setData('interaction_type', $interactionType);
        // Check for Merchant Token
        $wpp->setData('token_type', $this->worlpayhelper->getMerchantTokenization());
        $wpp->save();
    }


    /**
     * check paymentmethod is available for billing country
     *
     * @param $quote
     * @return bool
     * @throw Exception
     */
    protected function _checkpaymentapplicable($quote)
    {
        $type = strtoupper($this->_getpaymentType());
        $billingaddress = $quote->getBillingAddress();
        $countryId = $billingaddress->getCountryId();
        $paymenttypes = json_decode($this->paymenttypes->getPaymentType($countryId));
    }

    /**
     * payment method
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getpaymentType()
    {
        if (empty($this->paymentdetailsdata['additional_data']['tokenCode'])) {
            return $this->paymentdetailsdata['additional_data']['cc_type'];
        } else {
            $merchantTokenEnabled = $this->worlpayhelper->getMerchantTokenization();
            $tokenType = $merchantTokenEnabled ? 'merchant' : 'shopper';
            $savedCard = $this->_savecard->create()->getCollection()
                ->addFieldToSelect(['method'])
                ->addFieldToFilter('token_code', ['eq' => $this->paymentdetailsdata['additional_data']['tokenCode']])
                ->addFieldToFilter('token_type', ['eq' => $tokenType])
                ->getData();
            if ($savedCard) {
                return str_replace(["_CREDIT", "_DEBIT", "_ELECTRON"], "", $savedCard[0]['method']);
                //return $savedCard[0]['method'];
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Inavalid Card deatils. Please Refresh and check again')
                );
            }
        }
    }
}
