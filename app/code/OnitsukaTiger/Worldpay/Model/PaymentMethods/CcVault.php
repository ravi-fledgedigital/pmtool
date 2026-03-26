<?php

namespace OnitsukaTiger\Worldpay\Model\PaymentMethods;

use Exception;
use Magento\Payment\Model\InfoInterface;

class CcVault extends \Sapient\Worldpay\Model\PaymentMethods\CcVault
{
    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|\Magento\Vault\Model\Method\Vault|CcVault|\Sapient\Worldpay\Model\PaymentMethods\CcVault
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        $this->logger->info('Vault Authorize function executed');
        $payment->setAdditionalInformation('method', $payment->getMethod());
        self::$paymentDetails = $payment->getAdditionalInformation();
        $mageOrder = $payment->getOrder();
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        try {
            $orderCode = $this->_generateOrderCode($quote);
            $this->_createWorldPayPayment($payment, $orderCode, $quote->getStoreId(), $quote->getReservedOrderId());
            $authorisationService = $this->getAuthorisationService($quote->getStoreId());
            $authorisationService->authorizePayment(
                $mageOrder,
                $quote,
                $orderCode,
                $quote->getStoreId(),
                self::$paymentDetails,
                $payment
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error('Authorising payment failed.');
            $errormessage = $this->worlpayhelper->updateErrorMessage($e->getMessage(), $quote->getReservedOrderId());
            $this->logger->error($errormessage);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errormessage)
            );
        }
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param $orderCode
     * @param $storeId
     * @param $orderId
     * @param string $interactionType
     */
    private function _createWorldPayPayment(
        InfoInterface $payment,
        $orderCode,
        $storeId,
        $orderId,
        $interactionType = 'ECOM'
    ) {
        $paymentdetails = self::$paymentDetails;
        $integrationType =$this->worlpayhelper->getIntegrationModelByPaymentMethodCode($payment->getMethod(), $storeId);
        $wpp = $this->worldpaypayment->create();
        $wpp->setData('order_id', $orderId);
        $wpp->setData('payment_status', \Sapient\Worldpay\Model\Payment\State::STATUS_SENT_FOR_AUTHORISATION);
        $wpp->setData('worldpay_order_id', $orderCode);
        $wpp->setData('store_id', $storeId);
        $wpp->setData('merchant_id', $this->worlpayhelper->getMerchantCode($paymentdetails['cc_type']));
        $wpp->setData('3d_verified', $this->worlpayhelper->isDynamic3DEnabled());
        $wpp->setData('payment_model', $integrationType);
        $wpp->setData('payment_type', $paymentdetails['cc_type']);
        $wpp->setData('interaction_type', $interactionType);
        $wpp->save();
    }

    /**
     * @param $quote
     * @return string
     */
    private function _generateOrderCode($quote)
    {
        return $quote->getReservedOrderId();
    }
}
