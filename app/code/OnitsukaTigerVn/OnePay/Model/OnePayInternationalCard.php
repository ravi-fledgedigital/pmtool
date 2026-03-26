<?php

namespace OnitsukaTigerVn\OnePay\Model;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;

class OnePayInternationalCard extends \Ecomteck\OnePay\Model\OnePayInternationalCard
{
    /**
     * Availability options
     */
    protected $_canCapture                  = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoice = true;
    protected $_isOffline = false;
    protected $_canCapturePartial = true;

    protected $_canRefundInvoicePartial = true;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        private \OnitsukaTigerVn\OnePay\Model\OnepayRefund $onepayRefund,
        private \Ecomteck\OnePay\Helper\Data $onePayHelperData,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
    }

    /**
     * Authorizes specified amount.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return $this
     *
     * @throws LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param $amount
     * @return void
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // Example: Call your payment gateway to capture funds here
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        // Original transaction ID or reference
        $orderPrefix = $this->onePayHelperData->getDomesticCardOrderPrefix();
        $originalTxnRef = $orderPrefix ? $orderPrefix : 'ecomteck';

        $txnId = $originalTxnRef . $orderId;

        $payment->setTransactionId($txnId)
            ->setIsTransactionClosed(0); // Keep open if more captures/refunds possible

        // Optional: add transaction comment
        $payment->addTransactionCommentsToOrder(
            $payment->addTransaction(
                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
                null,
                true
            ),
            __('Captured online through OnePay.')
        );

    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // Get order details
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        // Original transaction ID or reference
        $websiteId = $order->getStore()->getWebsiteId();
        $orderPrefix = $this->getDomesticCardOrderPrefix($websiteId);
        $originalTxnRef = $orderPrefix ? $orderPrefix : 'ecomteck';

        $txnId = $originalTxnRef . $orderId;

        // Make API call to OnePAY refund endpoint (implement this service)
        $result = $this->onepayRefund->refund($txnId, $amount, $order);

        if (!empty($result) && isset($result['vpc_TxnResponseCode']) && $result['vpc_TxnResponseCode'] == '0') {
            $refundTransactionId = $txnId . '_' . $result['vpc_TransactionNo'];
            $payment->setTransactionId($refundTransactionId);
            $payment->setParentTransactionId($txnId); // Set parent to capture/authorization txn
            $payment->setIsTransactionClosed(true);

            // Create refund transaction
            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND, null, true, [
                'amount' => $amount,
                'info' => __('Refunded via OnePay gateway.'),
            ]);

            $payment->save();
            $order->save();
        } else {
            $statusCode = (isset($result['vpc_TxnResponseCode'])) ? $result['vpc_TxnResponseCode'] : null;
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Refund failed with status code: %1', $statusCode)
            );
        }

        return $this;
    }

    private function getDomesticCardOrderPrefix($websiteId)
    {
        return $this->_scopeConfig->getValue(
            $this->onePayHelperData::ONEPAY_DOMESTIC_CARD_ORDER_PREFIX,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
