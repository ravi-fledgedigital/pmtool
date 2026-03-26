<?php

namespace OnitsukaTigerVn\OnePay\Cron;

use Magento\Sales\Model\Order;

class QueryDr extends \Ecomteck\OnePay\Cron\QueryDr
{
    protected $orderFactory;

    /**
     * @var \Ecomteck\OnePay\Helper\Data
     */
    protected $onePayHelperData;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Sales\Model\OrderFactory                    $orderFactory
     * @param \Ecomteck\OnePay\Helper\Data                         $onePayHelperData
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Psr\Log\LoggerInterface                             $logger
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Ecomteck\OnePay\Helper\Data $onePayHelperData,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Psr\Log\LoggerInterface $logger,
        private \OnitsukaTigerVn\OnePay\Helper\Config $configHelper,
        private \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        private \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        private \Magento\Framework\DB\Transaction $transaction
    ) {
        parent::__construct($orderFactory, $onePayHelperData, $timezone, $logger);
    }

    /**
     * Processing update order status
     *
     * @return bool
     */
    public function execute()
    {

        $currentDateTime = strtotime(date('Y-m-d H:i:s'));
        $sixteenMinutesBefore = $currentDateTime - 17*60;
        $dateTimeSixteenMinutesBefore = date('Y-m-d H:i:s', $sixteenMinutesBefore);
        $orderCollection = $this->orderFactory
            ->create()
            ->getCollection()
            ->addFieldToFilter(
                'status',
                'pending'
            )->addFieldToFilter(
                'created_at',
                ['lteq' => $dateTimeSixteenMinutesBefore]
            );
        $orderIdsFromOnePayDomestic = [];
        $orderIdsFromOnePayInternational = [];
        foreach ($orderCollection as $order) {
            $payment = $order->getPayment();
            if ($payment) {
                $paymentMethod = $payment->getMethod();
                if ($paymentMethod == 'onepay_domestic') {
                    $orderIdsFromOnePayDomestic[] = $order->getIncrementId();
                } elseif ($paymentMethod == 'onepay_international') {
                    $orderIdsFromOnePayInternational[] = $order->getIncrementId();
                }
            }
        }
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/queryDr.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('=====Query DR log start=====');
        $logger->info('Order Ids: ' . json_encode($orderIdsFromOnePayDomestic));
        // Processing orders with the payment from OnePay Domestic
        if (!empty($orderIdsFromOnePayDomestic)) {
            $this->logger->critical('Starting update order status by QueryDR from OnePay');
            $merchantId = $this->configHelper->getDomesticCardMerchantId(6);
            $accessCode = $this->configHelper->getDomesticCardAccessCode(6);
            $queryDrUser = $this->configHelper->getDomesticCardQueryDrUser(6);
            $queryDrPassword = $this->configHelper->getDomesticCardQueryDrPassword(6);
            $orderPrefix = $this->configHelper->getDomesticCardOrderPrefix(6);
            $hasCode = $this->configHelper->getDomesticCardHashCode(6);
            $orderPrefix = $orderPrefix ? $orderPrefix : 'ecomteck';
            $logger->info('======Onepay Domestic Payment Method Log Start======');
            $logger->info('Merchant ID: ' . $merchantId);
            $logger->info('Access Code: ' . $accessCode);
            $logger->info('Query Dr User: ' . $queryDrUser);
            $logger->info('Query Dr Password: ' . $queryDrPassword);
            $logger->info('Order Prefix: ' . $orderPrefix);
            foreach ($orderIdsFromOnePayDomestic as $orderId) {
                $targetUrl = $this->configHelper->getDomesticCardQueryDrUrl(6);
                $logger->info('Target URL: ' . $targetUrl);
                $postFields = [
                    'vpc_Command' => 'queryDR',
                    'vpc_Merchant' => $merchantId,
                    'vpc_AccessCode' => $accessCode,
                    'vpc_MerchTxnRef' => $orderPrefix . $orderId,
                    'vpc_User' => $queryDrUser,
                    'vpc_Password' => $queryDrPassword,
                    'vpc_Version' => '2'
                ];

                ksort($postFields);
                $hashString = urldecode(http_build_query($postFields));
                $secureHash = hash_hmac('SHA256', $hashString, pack('H*', $hasCode));
                $postFields['vpc_SecureHash'] = $secureHash;
                /*$postFields =
                    'vpc_Command=queryDR&vpc_Version=2&vpc_MerchTxnRef=' .
                    $orderPrefix .
                    $orderId .
                    '&vpc_Merchant=' .
                    $merchantId .
                    '&vpc_AccessCode=' .
                    $accessCode .
                    '&vpc_User=' .
                    $queryDrUser .
                    '&vpc_Password=' . $queryDrPassword;*/
                $logger->info('Post Fields: ' . json_encode($postFields));
                try {
                    $this->logger->critical('Processing for Order Increment ID: ' . $orderId);
                    $postFields = http_build_query($postFields);
                    ob_start();
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $targetUrl);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                    $result = curl_exec($ch);
                    $logger->info('Result: ' . $result);
                    curl_close($ch);
                    if (empty($result) || !isset($result)) {
                        $this->logger->critical('The result is unknown due to connection problem');
                        ob_end_clean();
                        break;
                    }
                    $response = ob_get_contents();
                    $logger->info('Response: ' . $response);
                    ob_end_clean();
                    $this->logger->critical($response);
                    // search if $response contains HTML error code
                    if (strchr($response, '<html>')) {
                        $this->logger->critical('The result contains HTML error code');
                        break;
                    }
                    $responseCode = 'failed';
                    $orderInfo = '';
                    $params = explode('&', $response);
                    $map = [];
                    foreach ($params as $param) {
                        $explode = explode('=', $param);
                        if (count($explode) >= 2) {
                            $map[urldecode($explode[0])] = urldecode($explode[1]);
                        }
                    }
                    if (isset($map['vpc_TxnResponseCode'])) {
                        $responseCode = $map['vpc_TxnResponseCode'];
                    }
                    if (isset($map['vpc_OrderInfo'])) {
                        $orderInfo = $map['vpc_OrderInfo'];
                    }
                    if ($responseCode == '0' && $orderInfo == $orderId) {
                        /*$this->orderFactory->create()->loadByIncrementId($orderId)->setStatus(
                            \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW
                        )->save();*/
                        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
                        $this->createInvoice($order);
                        $this->logger
                            ->critical(
                                'Updated the status of order Increment ID: ' . $orderId . ' to ' . Order::STATE_PROCESSING
                            );
                    } elseif ($responseCode == '300' && $orderInfo == $orderId) {
                        $this->orderFactory
                            ->create()
                            ->loadByIncrementId($orderId)
                            ->setStatus('payment_onepay_pending')
                            ->save();
                        $this->logger
                            ->critical('Updated the status of order Increment ID: ' . $orderId . ' to "OnePay Pending"');
                    } else {
                        $this->orderFactory
                            ->create()
                            ->loadByIncrementId($orderId)
                            ->setStatus('payment_onepay_failed')
                            ->save();
                        $this->logger
                            ->critical('Updated the status of order Increment ID: ' . $orderId . ' to "OnePay Failed"');
                        if ($orderInfo != $orderId) {
                            $message =
                                'Error could not find order Increment ID: ' . $orderId . ' on OnePay Payment Gateway';

                            $this->logger->critical($message);
                            $this->orderFactory
                                ->create()
                                ->loadByIncrementId($orderId)
                                ->addStatusHistoryComment($message)
                                ->setIsCustomerNotified(false)
                                ->setEntityName('order')
                                ->save();
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                    return false;
                }
            }
            $this->logger->critical('The End.');
            $logger->info('======Onepay Domestic Payment Method Log End======');
        }

        // Processing orders with the payment from OnePay International
        if (!empty($orderIdsFromOnePayInternational)) {
            $this->logger->critical('Starting update order status by QueryDR from OnePay');
            $logger->info('======Onepay Internation Payment Method Log Start======');
            $merchantId = $this->configHelper->getInternationalCardMerchantId(6);
            $accessCode = $this->configHelper->getInternationalCardAccessCode(6);
            $queryDrUser = $this->configHelper->getInternationalCardQueryDrUser(6);
            $queryDrPassword = $this->configHelper->getInternationalCardQueryDrPassword(6);
            $orderPrefix = $this->configHelper->getInternationalCardOrderPrefix(6);
            $hasCode = $this->configHelper->getDomesticCardHashCode(6);
            $orderPrefix = $orderPrefix ? $orderPrefix : 'ecomteck';
            $logger->info('Merchant ID: ' . $merchantId);
            $logger->info('Access Code: ' . $accessCode);
            $logger->info('Query Dr User: ' . $queryDrUser);
            $logger->info('Query Dr Password: ' . $queryDrPassword);
            $logger->info('Order Prefix: ' . $orderPrefix);
            foreach ($orderIdsFromOnePayInternational as $orderId) {
                $targetUrl = $this->configHelper->getInternationalCardQueryDrUrl(6);
                $logger->info('Target URL: ' . $targetUrl);
                $postFields = [
                    'vpc_Command' => 'queryDR',
                    'vpc_Merchant' => $merchantId,
                    'vpc_AccessCode' => $accessCode,
                    'vpc_MerchTxnRef' => $orderPrefix . $orderId,
                    'vpc_User' => $queryDrUser,
                    'vpc_Password' => $queryDrPassword,
                    'vpc_Version' => '2'
                ];

                ksort($postFields);
                $hashString = urldecode(http_build_query($postFields));
                $secureHash = hash_hmac('SHA256', $hashString, pack('H*', $hasCode));
                $postFields['vpc_SecureHash'] = $secureHash;
                /*$postFields =
                    'vpc_Command=queryDR&vpc_Version=2&vpc_MerchTxnRef=' .
                    $orderPrefix .
                    $orderId .
                    '&vpc_Merchant=' .
                    $merchantId .
                    '&vpc_AccessCode=' .
                    $accessCode .
                    '&vpc_User=' .
                    $queryDrUser .
                    '&vpc_Password=' .
                    $queryDrPassword;*/
                $logger->info('Post Fields: ' . json_encode($postFields));
                try {
                    $this->logger->critical('Processing for Order Increment ID: ' . $orderId);
                    $postFields = http_build_query($postFields);
                    ob_start();
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $targetUrl);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                    $result = curl_exec($ch);
                    $logger->info('Result: ' . $result);
                    curl_close($ch);
                    if (empty($result) || !isset($result)) {
                        $this->logger->critical('The result is unknown due to connection problem');
                        ob_end_clean();
                        break;
                    }
                    $response = ob_get_contents();
                    $logger->info('Response: ' . $response);
                    ob_end_clean();
                    $this->logger->critical($response);
                    // search if $response contains HTML error code
                    if (strchr($response, '<html>')) {
                        $this->logger->critical('The result contains HTML error code');
                        break;
                    }
                    $responseCode = 'failed';
                    $orderInfo = '';
                    $params = explode('&', $response);
                    $map = [];
                    foreach ($params as $param) {
                        $explode = explode('=', $param);
                        if (count($explode) >= 2) {
                            $map[urldecode($explode[0])] = urldecode($explode[1]);
                        }
                    }
                    if (isset($map['vpc_TxnResponseCode'])) {
                        $responseCode = $map['vpc_TxnResponseCode'];
                    }
                    if (isset($map['vpc_OrderInfo'])) {
                        $orderInfo = $map['vpc_OrderInfo'];
                    }
                    if ($responseCode == '0' && $orderInfo == $orderId) {
                        /*$this->orderFactory->create()->loadByIncrementId($orderId)->setStatus(
                            \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW
                        )->save();*/
                        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
                        $this->createInvoice($order);
                        $this->logger
                            ->critical(
                                'Updated the status of order Increment ID: ' . $orderId . ' to ' . Order::STATE_PROCESSING
                            );
                    } else {
                        $this->orderFactory
                            ->create()
                            ->loadByIncrementId($orderId)
                            ->setStatus('payment_onepay_failed')
                            ->save();
                        $this->logger
                            ->critical('Updated the status of order Increment ID: ' . $orderId . ' to "OnePay Failed"');
                        if ($orderInfo != $orderId) {
                            $message =
                                'Error could not find order Increment ID: ' . $orderId . ' on OnePay Payment Gateway';
                            $this->logger->critical($message);
                            $this->orderFactory
                                ->create()
                                ->loadByIncrementId($orderId)
                                ->addStatusHistoryComment($message)
                                ->setIsCustomerNotified(false)
                                ->setEntityName('order')
                                ->save();
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                    return false;
                }
            }
            $this->logger->critical('The End.');
            $logger->info('======Onepay Internation Payment Method Log End======');
        }
        $logger->info('=====Query DR log End=====');
    }

    private function createInvoice($order)
    {
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE); // or CAPTURE_ONLINE if you integrate a gateway
            $invoice->register();
            $invoice->pay();
            $transactionSave = $this->transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
            $this->invoiceSender->send($invoice);
            //send notification code
            $order->addStatusHistoryComment(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )->setIsCustomerNotified(true);

            $order->setIsInProcess(true);
        }
    }
}
