<?php

namespace OnitsukaTiger\PortOne\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Payment\Model\InfoInterface;
use OnitsukaTiger\PortOne\Helper\Data as PortOneHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\RequestInterface;

class PaycoPayPayment extends AbstractMethod
{
    public const XML_PATH_IS_ENABLED = 'payment/portonepaycopay/active';

    protected $_code = 'portonepaycopay';

    protected $_isGateway = true;
    protected $_isInitializeNeeded = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    protected $scopeConfig;
    protected $portOneHelper;
    protected $request;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        PortOneHelper $portOneHelper,
        RequestInterface $request,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->portOneHelper = $portOneHelper;
        $this->request = $request;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $websiteId = null;

        if ($quote) {
            $websiteId = $quote->getStore()->getWebsiteId();
        }

        $isEnabled = $this->scopeConfig->isSetFlag(
            self::XML_PATH_IS_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        return $isEnabled && parent::isAvailable($quote);
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (is_array($additionalData)) {
            $infoInstance = $this->getInfoInstance();

            foreach ($additionalData as $key => $value) {
                if (!is_object($value) && !is_array($value)) {
                    $infoInstance->setAdditionalInformation($key, $value);
                }
            }
        }

        return $this;
    }

    public function capture(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $transactionId = 'portone_' . $order->getIncrementId();

        $payment->setTransactionId($transactionId)
                ->setIsTransactionClosed(false);

        $payment->addTransaction(
            \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
            null,
            false,
            'PortOne Bank Paycopay Capture'
        );

        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            throw new LocalizedException(__('Invalid refund amount specified.'));
        }

        $order = $payment->getOrder();
        $transactionId = $payment->getParentTransactionId() ?: $payment->getTransactionId() ?: $payment->getLastTransId();

        if (!$transactionId) {
            throw new LocalizedException(__('Transaction ID is missing for refund.'));
        }

        try {
            $fullActionName = $this->request->getFullActionName();
            $note = __("Admin generated credit memo.");
            if (in_array($fullActionName, ['cancelorder_customer_cancel', 'cancelorder_guest_cancel'])) {
                $note = __("Customer generated credit memo.");
            }

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/portone.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('*************************************************************');
            $logger->info('Order Id   '. $order->getIncrementId());
            $logger->info('Note       '. $note);
            $logger->info('Amount     '. $amount);
            $logger->info('*************************************************************');

            $response = $this->portOneHelper->getOrderCancelOrRefund($order, $note, $amount);

            if (!$response['success']) {
                throw new LocalizedException(__('Refund failed: %1', $response['message']));
            }

            $payment->setTransactionId('portone_refund_' . time() . '_' . $order->getIncrementId());
            $payment->addTransaction(
                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND,
                null,
                false,
                'Online refund via PortOne Bank Paycopay'
            );

            $payment->save();

        } catch (\Exception $e) {
            throw new LocalizedException(__('Refund error: %1', $e->getMessage()));
        }

        return $this;
    }
}
