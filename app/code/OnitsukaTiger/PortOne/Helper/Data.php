<?php
declare(strict_types=1);

namespace OnitsukaTiger\PortOne\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\HTTP\Client\Curl;
use OnitsukaTiger\PortOne\Logger\Logger;
use OnitsukaTiger\PortOne\Model\ResourceModel\PortOne\CollectionFactory as PortOneCollectionFactory;
use OnitsukaTiger\PortOne\Model\PortOneFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;

class Data extends AbstractHelper
{
    public const PORTONE_API_URL     = 'https://api.portone.io/';

    public const XML_PATH_PORTONE  = 'portone';
    public const XML_PATH_TRANSFER = 'portonetransfer';
    public const XML_PATH_EASYPAY  = 'portone_easypay';
    public const XML_PATH_TOSSPAY  = 'portone_tosspay';
    public const XML_PATH_NPAY     = 'portone_npay';
    public const XML_PATH_KAKAOPAY = 'portone_kakaopay';
    public const XML_PATH_PAYCOPAY = 'portonepaycopay';

    public const XML_PATH_SAMSUNGPAY = 'portonesamsungpay';

    public const XML_PATH_APPLEPAY = 'portoneapplepay';

    private StoreManagerInterface $storeManager;
    private Logger $logger;
    private PortOneCollectionFactory $portOneCollectionFactory;
    private Curl $curl;
    private PortOneFactory $portoneFactory;
    private InvoiceService $invoiceService;
    private Transaction $transaction;
    private InvoiceSender $invoiceSender;
    private CreditmemoFactory $creditmemoFactory;
    protected CreditmemoSender $creditmemoSender;

    private int $currentStoreId;
    private int $currentWebsiteId;

    /**
     * Data Helper Constructor.
     *
     * @param Context $context
     * @param Logger $logger
     * @param PortOneCollectionFactory $portOneCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param PortOneFactory $portoneFactory
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param InvoiceSender $invoiceSender
     * @param CreditmemoFactory $creditmemoFactory
     * @param CreditmemoSender $creditmemoSender
     */
    public function __construct(
        Context $context,
        Logger $logger,
        PortOneCollectionFactory $portOneCollectionFactory,
        StoreManagerInterface $storeManager,
        Curl $curl,
        PortOneFactory $portoneFactory,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoSender $creditmemoSender
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->portOneCollectionFactory = $portOneCollectionFactory;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->portoneFactory = $portoneFactory;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoSender = $creditmemoSender;

        $store = $this->storeManager->getStore();
        $this->currentStoreId   = (int) $store->getId();
        $this->currentWebsiteId = (int) $store->getWebsiteId();
    }

    /**
     * Get current store ID.
     *
     * @return int
     */
    public function getCurrentStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    /**
     * Get current website ID.
     *
     * @return int
     */
    public function getCurrentWebsiteId(): int
    {
        return (int) $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * Get configuration value by scope.
     *
     * @param string $path
     * @param string $scopeType
     * @param int|null $scopeId
     * @return string|null
     */
    public function getConfigValue(
        string $path,
        string $scopeType = ScopeInterface::SCOPE_STORE,
               $scopeId = null
    ): ?string {
        return (string) $this->scopeConfig->getValue(
            $path,
            $scopeType,
            $scopeId ?? $this->currentStoreId
        );
    }

    /**
     * Check if payment method is enabled.
     *
     * @param string $path
     * @param int|null $websiteId
     * @return bool
     */
    public function isEnabled(string $path, $websiteId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            "payment/".$path."/active",
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId ?? $this->currentWebsiteId
        );
    }

    /**
     * Get payment method title.
     *
     * @param string $path
     * @param int|null $storeId
     * @return string|null
     */
    public function getTitle(string $path, $storeId = null): ?string
    {
        return $this->getConfigValue(
            "payment/".$path."/title",
            ScopeInterface::SCOPE_STORE,
            $storeId ?? $this->currentStoreId
        );
    }

    /**
     * Get payment channel key.
     *
     * @param string $path
     * @param int|null $websiteId
     * @return string|null
     */
    public function getChannelKey(string $path, $websiteId = null): ?string
    {
        return $this->getConfigValue(
            "payment/".$path."/payment_channel_key",
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId ?? $this->currentWebsiteId
        );
    }

    /**
     * Get payment store ID.
     *
     * @param string $path
     * @param int|null $websiteId
     * @return string|null
     */
    public function getStoreId(string $path, $websiteId = null): ?string
    {
        return $this->getConfigValue(
            "payment/".$path."/store_id",
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId ?? $this->currentWebsiteId
        );
    }

    /**
     * Get payment API key.
     *
     * @param string $path
     * @param int|null $websiteId
     * @return string|null
     */
    public function getAPIKey(string $path, $websiteId = null): ?string
    {
        return $this->getConfigValue(
            "payment/".$path."/api_key",
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId ?? $this->currentWebsiteId
        );
    }

    /**
     * Get payment configuration data.
     *
     * @param string $path
     * @return array
     */
    public function getPayMethod(string $path, $websiteId = null): ?string
    {
        return $this->getConfigValue(
            "payment/".$path."/pay_method",
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId ?? $this->currentWebsiteId
        );
    }

    /**
     * Get payment configuration data.
     *
     * @param string $path
     * @return array
     */
    public function getEasyPayProvider(string $path, $websiteId = null): ?string
    {
        return $this->getConfigValue(
            "payment/".$path."/easy_pay_provider",
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId ?? $this->currentWebsiteId
        );
    }

    /**
     * Generate authorization header value.
     *
     * @param string $path
     * @param int|null $websiteId
     * @return string
     */
    public function getAuthorization(string $path, $websiteId = null): string
    {
        return 'PortOne ' . $this->getAPIKey($path, $websiteId);
    }

    /**
     * Get payment configuration data.
     *
     * @param string $path
     * @return array
     */
    public function getPaymentConfig(string $path): array
    {
        return [
            'isEnable'   => $this->isEnabled($path),
            'title'      => $this->getTitle($path),
            'channelKey' => $this->getChannelKey($path),
            'storeId'    => $this->getStoreId($path),
            'payMethod'  => $this->getPayMethod($path),
            'easyPayProvider' => $this->getEasyPayProvider($path)
        ];
    }

    /**
     * Generate Order Refund Online
     *
     * @param Order $order
     * @param string $reason
     * @param float $amount
     * @return array $result
     */
    public function getOrderCancelOrRefund(Order $order, string $reason, float $amount)
    {
        try {
            $result = [];
            $portOneModelCollection = $this->portOneCollectionFactory
                ->create()
                ->addFieldToFilter('order_entity_id', $order->getId())
                ->getFirstItem();

            $paymentId     = $portOneModelCollection->getPaymentId();
            $orderEntityId = $portOneModelCollection->getOrderEntityId();
            $txid          = $portOneModelCollection->getTxid();

            if (!$paymentId) {
                $this->logger->warning(
                    sprintf("PortOne refund skipped: no payment ID for order #%s", $order->getIncrementId())
                );
                $result['message'] = __("PortOne refund skipped: no payment ID for order %1", $order->getIncrementId());
                $result['success'] = false;
                return $result;
            }

            $url     = self::PORTONE_API_URL . "payments/{$paymentId}/cancel";
            $payload = json_encode(['reason' => $reason, "amount" => $amount, "skipWebhook" => true]);

            $this->logger->info('Creditmemo Payload  '.print_r($payload, true));

            $payment = $order->getPayment();
            $websiteId = $order->getStore()->getWebsiteId();
            $method = $payment->getMethod();

            $allowedMethods = [
                self::XML_PATH_TRANSFER,
                self::XML_PATH_EASYPAY,
                self::XML_PATH_TOSSPAY,
                self::XML_PATH_NPAY,
                self::XML_PATH_KAKAOPAY,
                self::XML_PATH_PAYCOPAY,
                self::XML_PATH_SAMSUNGPAY,
                self::XML_PATH_APPLEPAY
            ];

            $apiPath = in_array($method, $allowedMethods, true)
                ? $method
                : self::XML_PATH_PORTONE;

            $this->curl->addHeader('Authorization', $this->getAuthorization($apiPath, $websiteId));
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->post($url, $payload);

            $statusCode = $this->curl->getStatus();
            $response   = $this->curl->getBody();

            $this->logger->info(
                sprintf("PortOne refund response for order #%s: [HTTP %d] %s", $order->getIncrementId(), $statusCode, $response)
            );

            $portOneModel   = $this->portoneFactory->create();
            $pgCancellationId = $cancelReason = '';
            $status = 0;
            if ($statusCode === 200 && $response) {
                $decoded = json_decode($response, true);

                if (isset($decoded['cancellation'])) {
                    $cancelReason     = $decoded['cancellation']['reason'] ?? 'N/A';
                    $pgCancellationId = $decoded['cancellation']['pgCancellationId'] ?? 'N/A';
                    $transactionType  = 'Refund';
                    $status           = 2;

                    $result['success'] = true;
                    $this->logger->info(
                        sprintf("PortOne refund order #%s with reason: %s", $order->getIncrementId(), $cancelReason)
                    );
                }
            } else {
                $decoded       = json_decode($response, true);
                $cancelReason  = $decoded['message'] ?? 'Unknown error';
                $transactionType = 'Failed';
                $status        = 0;

                $result['message'] = $cancelReason;
                $result['success'] = false;
                $this->logger->info(
                    sprintf("PortOne refund order issue #%s with reason: %s", $order->getIncrementId(), $cancelReason)
                );
            }

            $portOneModel->setOrderEntityId($orderEntityId);
            $portOneModel->setPaymentId($paymentId);
            $portOneModel->setTransactionType($transactionType);
            $portOneModel->setTxid($pgCancellationId);
            $portOneModel->setStatus($status);
            $portOneModel->setFullContent($response ?? '');
            $portOneModel->setMessage($cancelReason);
            $portOneModel->save();

            $historyComment = ($statusCode === 200 && $response)
                ? __(
                    'PortOne payment has been successfully refunded online.<br /><b>Reason:</b> %1<br /><b>pgCancellationId:</b> %2',
                    $cancelReason ?? '-',
                    $pgCancellationId ?? '-'
                )
                : __(
                    'PortOne payment cancel issue.<br /><b>Status Code:</b> %1<br /><b>Status Type:</b> %2<br /><b>Reason:</b> %3',
                    $statusCode,
                    $decoded['type'] ?? 'N/A',
                    $decoded['message'] ?? 'Unknown error'
                );

            $order->addStatusHistoryComment($historyComment, false);
            $order->save();
            $this->logger->info('***********************************************');
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf("PortOne cancel exception for order #%s: %s", $order->getIncrementId(), $e->getMessage())
            );
        }
    }

    /**
     * Generate invoice for order
     *
     * @param Order $order
     * @return array $result
     */
    public function generateInvoice(Order $order)
    {
        $result = ['success' => false];
        if (!$order->canInvoice()) {
            return $this->buildResult('Order #'.$order->getIncrementId().' cannot be invoiced.');
        }

        try {
            $invoice = $this->invoiceService->prepareInvoice($order);

            if (!$invoice) {
                return $this->buildResult('Cannot create invoice.');
            }

            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->pay();
            $invoice->getOrder()->setIsInProcess(true);

            $transactionId = 'portone_capture_' . time() . '_' . $order->getIncrementId();

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId)
                ->setParentTransactionId($transactionId)
                ->setIsTransactionClosed(false);

            $captureTransaction = $payment->addTransaction(
                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
                $invoice,
                false,
                'Online capture with PortOne'
            );
            $payment->save();

            $this->transaction
                ->addObject($captureTransaction)
                ->addObject($invoice)
                ->addObject($order)
                ->save();

            $this->invoiceSender->send($invoice);

            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
                ->addStatusHistoryComment(__('Invoice #%1 generated with online capture.', $invoice->getIncrementId()))
                ->setIsCustomerNotified(true)
                ->save();

            return ['success' => true, 'message' => 'Invoice generated and payment captured for Order ID ' . $order->getIncrementId()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => __('Invoice generation error: %1', $e->getMessage())];
        }
    }

    /**
     * Generate creditmemo for order
     *
     * @param Order $order
     * @return array $result
     */
    public function generateCreditMemo(Order $order): array
    {
        $result = ['success' => false];

        try {
            if (!$order->canCreditmemo()) {
                return $this->buildResult("Order ID {$order->getIncrementId()} is not eligible for credit memo.");
            }

            if ($order->getCreditmemosCollection()->getSize() > 0) {
                return $this->buildResult("Credit memo already exists for Order ID {$order->getIncrementId()}. Not creating duplicate.");
            }

            $invoice = $order->getInvoiceCollection()->getFirstItem();
            if (!$invoice || !$invoice->getId()) {
                return $this->buildResult("No invoice found for Order ID {$order->getIncrementId()} to generate credit memo.", 'error');
            }

            $creditmemo = $this->creditmemoFactory->createByOrder($order);
            $creditmemo->setInvoice($invoice);
            $creditmemo->save();

            $this->transaction
                ->addObject($creditmemo)
                ->addObject($creditmemo->getOrder())
                ->save();

            $this->creditmemoSender->send($creditmemo);

            $order->setState(\Magento\Sales\Model\Order::STATE_CLOSED)
                ->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED);
            $order->addStatusHistoryComment(
                __('Credit memo #%1 created and customer notified.', $creditmemo->getIncrementId())
            );
            $order->setIsCustomerNotified(true);
            $order->save();

            $this->logger->info("Credit memo created: Order ID {$order->getIncrementId()}, Credit Memo ID {$creditmemo->getIncrementId()}");

            return ['success' => true, 'message' => 'Credit memo generated successfully', 'status' => $order->getStatus()];
        } catch (\Exception $e) {
            $errorMsg = "Failed to generate credit memo for Order ID {$order->getIncrementId()}: " . $e->getMessage();
            $this->logger->error($errorMsg);
            return ['success' => false, 'message' => $errorMsg];
        }
    }

    /**
     * Generate creditmemo for order message with status
     *
     * @param string $message
     * @param string $logLevel
     * @return array
     */
    protected function buildResult(string $message, string $logLevel = 'info'): array
    {
        if ($logLevel === 'error') {
            $this->logger->error($message);
        } else {
            $this->logger->info($message);
        }

        return ['success' => false, 'message' => $message];
    }
}
