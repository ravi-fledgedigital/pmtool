<?php
/**
 * Notification
 */

namespace OnitsukaTigerIndo\Midtrans\Preference\Controller\Payment;

use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Http\Context as ContextHttp;
use Magento\Framework\DB\Transaction;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Model\Service\InvoiceService;
use Midtrans\Snap\Helper\MidtransDataConfiguration;
use Midtrans\Snap\Logger\MidtransLogger;
use Midtrans\Snap\Model\PaymentRequestRepository;

class Notification extends \Midtrans\Snap\Controller\Payment\Notification
{

    public function __construct(Context $context, SessionManagerInterface $coreSession, Session $checkoutSession, Order $order, ObjectManagerInterface $objectManager, MidtransDataConfiguration $midtransDataConfiguration, InvoiceService $invoiceService, Transaction $transaction, ResourceModel $resourceModel, OrderRepository $orderRepository, Order\Invoice $invoice, Order\CreditmemoFactory $creditmemoFactory, CreditmemoService $creditmemoService, Order\CreditmemoRepository $creditmemoRepository, MidtransLogger $midtransLogger, Registry $registry, CustomerSession $customerSession, ContextHttp $contextHttp, PageFactory $pageFactory, \Midtrans\Snap\Model\Order\OrderRepository $paymentOrderRepository, PaymentRequestRepository $paymentRequestRepository)
    {
        parent::__construct($context, $coreSession, $checkoutSession, $order, $objectManager, $midtransDataConfiguration, $invoiceService, $transaction, $resourceModel, $orderRepository, $invoice, $creditmemoFactory, $creditmemoService, $creditmemoRepository, $midtransLogger, $registry, $customerSession, $contextHttp, $pageFactory, $paymentOrderRepository, $paymentRequestRepository);
    }

    /**
     * Process Midtrans notification with Magento order
     *
     * @param Order $order
     * @param $midtransStatusResult
     * @param $rawBody
     * @return mixed
     * @throws \Exception
     */
    public function processOrder(Order $order, $midtransStatusResult, $rawBody)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/pre_order_status_issue.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("--------------------processOrder() Start-------------------------------");
        $logger->info("Order Id :".$order->getIncrementId());
        $logger->info("Before Order Status : ".$order->getStatus());
        $logger->info("Before Order State : ".$order->getState());
        $midtransOrderId = $midtransStatusResult->order_id;
        $grossAmount = $midtransStatusResult->gross_amount;
        $transaction = $midtransStatusResult->transaction_status;
        $fraud = $midtransStatusResult->fraud_status;
        $payment_type = $midtransStatusResult->payment_type;
        $trxId = $midtransStatusResult->transaction_id;

        $note_prefix = "MIDTRANS NOTIFICATION  |  ";
        $order_note = $note_prefix . 'Payment Completed - ' . $payment_type;
        $payment = $order->getPayment();
        if ($transaction == 'capture') {
            $logger->info("Transaction Status : Capture");
            $payment->setTransactionId($trxId);
            $payment->setIsTransactionClosed(false);
            $this->paymentOrderRepository->setPaymentInformation($order, $trxId, $payment_type);
            if ($fraud == 'challenge') {
                $logger->info("Fraud Status : Challenge");
                $order_note = $note_prefix . 'Payment status challenged. Please take action on your Midtrans Dashboard - ' . $payment_type;
                $payment->setIsFraudDetected(true);
                $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PAYMENT_REVIEW, $order_note);
            } elseif ($fraud == 'accept') {
                $logger->info("Fraud Status : Accept");
                $payment->setIsFraudDetected(false);
                $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, null, true);
                $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PROCESSING, $order_note);
                $this->paymentOrderRepository->generateInvoice($order, $trxId);
                if ($order->getIsPreOrder()) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $order = $objectManager->create('\Magento\Sales\Model\Order')->load($order->getId());
                    $orderState = Order::STATE_PROCESSING;
                    $order->setState($orderState)->setStatus($orderState);
                    $order->save();
                    $logger->info("Order Is Pre-order : True");
                    $this->setOrderStateAndStatus($order, Order::STATE_PROCESSING, "pre_order_processing", $order_note);
                }
                $logger->info("After Order Status : ".$order->getStatus());
                $logger->info("After Order State : ".$order->getState());
            }
        } elseif ($transaction == 'settlement') {
            $logger->info("Transaction Status : Settlement");
            $payment->setIsFraudDetected(false);
            $payment->setTransactionId($trxId);
            $payment->setIsTransactionClosed(true);
            $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, null, true);
            if ($payment_type != 'credit_card') {
                $logger->info("Payment method is not Credit Card");
                $this->paymentOrderRepository->setPaymentInformation($order, $trxId, $payment_type);
                $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PROCESSING, $order_note);
                $this->paymentOrderRepository->generateInvoice($order, $trxId);
            }
        } elseif ($transaction == 'pending') {
            $logger->info("Transaction Status : Pending");
            $this->paymentOrderRepository->setPaymentInformation($order, $trxId, $payment_type);
            $order_note = $note_prefix . 'Awaiting Payment - ' . $payment_type;
            $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PENDING_PAYMENT, $order_note);
        } elseif ($transaction == 'cancel') {
            $logger->info("Transaction Status : Cancel");
            $order_note = $note_prefix . 'Canceled Payment - ' . $payment_type;
            // add to transaction menu record list if cancel req for status capture only
            if ($order->hasInvoices()) {
                $logger->info("Order Has Invoices");
                $payment = $order->getPayment();
                $payment->setParentTransactionId($trxId);
                $payment->setIsTransactionClosed(true);
                $payment->setTransactionId($trxId . '-' . strtoupper($transaction));
                $payment->addTransaction(TransactionInterface::TYPE_VOID, null, true);
            }
            $this->paymentOrderRepository->cancelOrder($order, Order::STATE_CANCELED, $order_note);
        } elseif ($transaction == 'expire') {
            $logger->info("Transaction Status : Expire");
            if ($order->canCancel()) {
                $order_note = $note_prefix . 'Expired Payment - ' . $payment_type;
                $this->paymentOrderRepository->cancelOrder($order, Order::STATE_CANCELED, $order_note);
            }
        } elseif ($transaction == 'deny') {
            $logger->info("Transaction Status : Deny");
            $this->paymentOrderRepository->setPaymentInformation($order, $trxId, $payment_type);
            $order_note = $note_prefix . 'Payment Deny - ' . $payment_type;
            $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PAYMENT_REVIEW, $order_note);
        } elseif ($transaction == 'refund' || $transaction == 'partial_refund') {
            $logger->info("Transaction Status : Refund/Partial Refund");
            /**
             * Do not process if the notification contain 'bank_confirmed_at' from request body
             */
            $refundRaw[] = end($rawBody['refunds']);
            if (isset($refundRaw[0]['bank_confirmed_at'])) {
                return $this->getResponse()->setBody('OK');
            } else {
                /**
                 * Get last array object from refunds array
                 */
                $refunds = $midtransStatusResult->refunds;
                $refund[] = end($refunds);
                $refund_reason = $refund[0]->reason;

                /**
                 * Get order-id from refund reason, this is process refund from Magento dashboard
                 */
                $midtransOrderId = $this->getOrderIdFromReason($refund_reason);
                if ($midtransOrderId !== null) {
                    $logger->info("Midtrans Order Id");
                    $orderRefund = $this->paymentOrderRepository->getOrderByIncrementId($midtransOrderId);
                    $this->processRefund($orderRefund, $refunds, true, $grossAmount);
                } else {
                    /**
                     * if order-id not found in reasons, handle as refund from MAP
                     */
                    $midtransOrderId = $midtransStatusResult->order_id;
                    $logger->info("Midtrans Order Id".$midtransOrderId);
                    /** Check order-id is not contain multishipping */
                    if (strpos($midtransOrderId, 'multishipping-') !== true) {
                        $order = $this->paymentOrderRepository->getOrderByIncrementId($midtransOrderId);
                        $this->processRefund($order, $refunds, false, $grossAmount);
                    }
                }
            }
        }
        $this->paymentOrderRepository->saveOrder($order);
        /**
         * If log request isEnabled, add request payload to var/log/midtrans/request.log
         */
        $_info = "status : " . $transaction . " , order : " . $midtransOrderId . ", payment type : " . $payment_type;

        $this->_midtransLogger->midtransNotification($_info);

        $logger->info("--------------------processOrder() End-------------------------------");

        if ($transaction == 'capture') {
            $logger->info("After Condition Transaction Status : Capture");
            if ($fraud == 'accept') {
                $logger->info("After Condition Fraud Status : Accept");
                if ($order->getIsPreOrder()) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $order = $objectManager->create('\Magento\Sales\Model\Order')->load($order->getId());
                    $orderState = Order::STATE_PROCESSING;
                    $order->setState($orderState)->setStatus($orderState);
                    $order->save();
                    $logger->info("After Condition Order Is Pre-order : True");
                }
                $logger->info("After Condition After Order Status : ".$order->getStatus());
                $logger->info("After Condition After Order State : ".$order->getState());
            }
        }

    }

    /**
     * Function to get Magento order-id from reasons refund,
     * the function used for request refund from Magento dashboard
     *
     * @param $refundReason
     * @return mixed|string|null
     */
    protected function getOrderIdFromReason($refundReason)
    {
        $array = explode(":::", $refundReason);
        if (isset($array[1])) {
            return $array[1];
        } else {
            return null;
        }
    }

    /**
     * Handling refund from Magento dashboard and Midtrans MAP
     *
     * @param Order $orderRefund
     * @param $refunds
     * @param $isFromMagento
     * @param null $grossAmount
     * @throws \Exception
     */
    protected function processRefund(Order $orderRefund, $refunds, $isFromMagento, $grossAmount = null)
    {
        $refund[] = end($refunds);
        $refundAmount = $refund[0]->refund_amount;
        $refund_reason = $refund[0]->reason;

        $isFullRefund = $this->isFullRefund($refunds, $orderRefund, $isFromMagento, $grossAmount);
        $refund_note = 'MIDTRANS NOTIFICATION | Refunded: ' . $refundAmount . '  |  Reason: ' . $refund_reason;

        /** Handling full refund */
        if ($isFullRefund && $orderRefund->getStatus() != Order::STATE_CLOSED && $orderRefund->getState() != Order::STATE_CLOSED) {
            $this->paymentOrderRepository->cancelOrder($orderRefund, Order::STATE_CLOSED, $refund_note);
        } /** Handling partial refund */
        elseif ($orderRefund->getStatus() != Order::STATE_CLOSED && $orderRefund->getState() != Order::STATE_CLOSED) {
            /** Do not process if notif history already exist */
            if (!$this->isOrderCommentExist($orderRefund, $refund_note)) {
                if ($isFullRefund) {
                    /** Close order if total amount refund array is equal with grand total order / gross amount */
                    $this->paymentOrderRepository->cancelOrder($orderRefund, Order::STATE_CLOSED, $refund_note);
                } else {
                    /** Put status history if total amount refund array is not equal with grand total order / gross amount */
                    $this->paymentOrderRepository->setOrderStateAndStatus($orderRefund, Order::STATE_PROCESSING, $refund_note);
                }
            }
        } /**
         * Skip refund process if not qualified
         */
        else {
            $this->getResponse()->setBody('OK');
        }
    }

    /**
     * Function to check request refund is full/partial refund
     *
     * @param array $refunds
     * @param Order $order
     * @param $isFromMagento
     * @param null $grossAmount
     * @return bool
     */
    protected function isFullRefund(array $refunds, Order $order, $isFromMagento, $grossAmount = null)
    {
        $orderId = $order->getIncrementId();
        $midtransOrderId = (string)$order->getPayment()->getAdditionalInformation('midtrans_order_id');
        $orderAmount = (double)$order->getGrandTotal();
        $refundAmount = null;
        /** count refund amount from Magento dashboard */
        if ($isFromMagento) {
            foreach ($refunds as $refund) {
                $refundOrderId = $this->getOrderIdFromReason($refund->reason);
                if ($orderId === $refundOrderId) {
                    $refundAmount += (double)$refund->refund_amount;
                }
            }
        } /** count refund amount from Midtrans dashboard */
        else {
            /** for multishipping */
            if (strpos($midtransOrderId, 'multishipping-') !== false) {
                if ($grossAmount !== null) {
                    foreach ($refunds as $refund) {
                        $refundAmount += (double)$refund->refund_amount;
                    }
                }
                return (double)$grossAmount === $refundAmount;
            } /** for regular order */
            else {
                foreach ($refunds as $refund) {
                    $refundAmount += (double)$refund->refund_amount;
                }
            }
        }
        return $orderAmount === $refundAmount;
    }

    /**
     * Function to check comment history notification is exist or not
     *
     * @param Order $order
     * @param $comment
     * @return bool
     */
    protected function isOrderCommentExist(Order $order, $comment)
    {
        $commentStatusHistory = $order->getStatusHistories();
        foreach ($commentStatusHistory as $value) {
            if (strpos($value->getComment(), $comment) !== false) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param Order $order
     * @param $status
     * @param $order_note
     * @return void
     * @throws \Exception
     */
    public function setOrderStateAndStatus(Order $order, $state, $status, $order_note)
    {
        $order->setState($state);
        $order->setStatus($status);
        $order->addStatusToHistory($status, $order_note, false);
        $this->paymentOrderRepository->saveOrder($order);
    }
}
