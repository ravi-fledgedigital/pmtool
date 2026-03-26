<?php

namespace Midtrans\Snap\Controller\Payment;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Midtrans\Snap\Gateway\Utility\PaymentUtils;

/**
 * Class Notification
 * Handle notifications from midtrans http notifications
 */
class Notification extends Action
{
    /**
     * Main function
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        // 1. Get body from request
        $input_source = $this->getRequest()->getContent();
        $rawBody = $this->midtransDataConfiguration->json->unserialize($input_source);
        $orderIdRequest = $rawBody['order_id'];
        $transactionId = $rawBody['transaction_id'];
        $paymentType = $rawBody['payment_type'];

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/midtrans/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("---------Midtrans Notification Received Start---------");
        $logger->info("Row Data: " . print_r($rawBody, true));
        $logger->info("Order Id: " . $orderIdRequest);
        $logger->info("Transaction ID: " . $transactionId);
        $logger->info("Payment Type: " . $paymentType);

        // Check if order for multishipping
        if (strpos($orderIdRequest, 'multishipping-') !== false) {
            $logger->info("Inside Multishipping");
            // 2. Process notification multishipping
            $quoteId = str_replace('multishipping-', '', $orderIdRequest);
            $incrementIds = $this->paymentOrderRepository->getIncrementIdsByQuoteId($quoteId);

            $this->getResponse()->setBody('ok');
            foreach ($incrementIds as $id) {
                $entityId = $this->paymentOrderRepository->getOrderByIncrementId($id)->getEntityId();
                $order  = $this->paymentOrderRepository->getOrderByEntityId($entityId);
                $paymentCode = $order->getPayment()->getMethod();

                if ($this->paymentOrderRepository->canProcess($order)) {
                    $midtransStatusResult = $this->midtransGetStatus($orderIdRequest, $paymentCode, $transactionId, $paymentType);
                    $this->processOrder($order, $midtransStatusResult, $rawBody);
                } else {
                    return $this->getResponse()->setBody('404 Order not found');
                }
            }
            // if not multishipping order
        } elseif ($orderIdRequest !== null) {
            $logger->info("Not Multishipping");
            // 3. Process notification regular order
            $this->getResponse()->setBody('OK');
            $order = $this->_order->loadByIncrementId($orderIdRequest);
            if ($this->paymentOrderRepository->canProcess($order) && !PaymentUtils::isOpenApi($paymentType)) {
                $logger->info("Inside IF");
                $midtransStatusResult = $this->midtransGetStatus($order, null, null, $paymentType);
                $logger->info("Midtrans Status: " . json_encode($midtransStatusResult));
                $this->processOrder($order, $midtransStatusResult, $rawBody);
            } else if (PaymentUtils::isOpenApi($paymentType)){
                $logger->info("Inside Else");
                $midtransStatusResult = $this->midtransGetStatus($order, null, $transactionId, $paymentType);
                $logger->info("Midtrans Status: " . json_encode($midtransStatusResult));
                $midOrderId = $midtransStatusResult->order_id;
                $logger->info("Midtrans Order ID: " . $midOrderId);
                $order = $this->_order->loadByIncrementId($midOrderId);
                if ($this->paymentOrderRepository->canProcess($order)){
                    $this->processOrder($order, $midtransStatusResult, $rawBody);
                } else {
                    $logger->info("First Else: 404 Order not found");
                    return $this->getResponse()->setBody('404 Order not found');
                }
            } else {
                $logger->info("Second Else: 404 Order not found");
                return $this->getResponse()->setBody('404 Order not found');
            }
        } else {
            $logger->info("Third Else: 404 Order not found");
            return $this->getResponse()->setBody('404 Order not found');
        }
        $logger->info("---------Midtrans Notification Received End---------");
        $this->unSetValue();
        return $this->getResponse()->setBody('ok');
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
        $midtransOrderId = $midtransStatusResult->order_id;
        $grossAmount = $midtransStatusResult->gross_amount;
        $transaction = $midtransStatusResult->transaction_status;
        $fraud = $midtransStatusResult->fraud_status;
        $payment_type = $midtransStatusResult->payment_type;
        $trxId = $midtransStatusResult->transaction_id;

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/midtrans/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("---------Midtrans Notification Process Order Start---------");
        $logger->info("Midtrans Order Id: " . $midtransOrderId);
        $logger->info("Gross Amount: " . $grossAmount);
        $logger->info("Transaction: " . $transaction);
        $logger->info("Fraud: " . $fraud);
        $logger->info("Payment Type: " . $payment_type);
        $logger->info("Txn ID: " . $trxId);

        $note_prefix = "MIDTRANS NOTIFICATION  |  ";
        $order_note = $note_prefix . 'Payment Completed - ' . $payment_type;
        $logger->info("Order Note: " . $order_note);
        $payment = $order->getPayment();
        if ($transaction == 'capture') {
            $logger->info("Transaction Captured");
            $payment->setTransactionId($trxId);
            $payment->setIsTransactionClosed(false);
            $this->paymentOrderRepository->setPaymentInformation($order, $trxId, $payment_type);
            if ($fraud == 'challenge') {
                $logger->info("Inside fraud challenge");
                $order_note = $note_prefix . 'Payment status challenged. Please take action on your Midtrans Dashboard - ' . $payment_type;
                $logger->info("Order Note: " . $order_note);
                $payment->setIsFraudDetected(true);
                $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PAYMENT_REVIEW, $order_note);
            } elseif ($fraud == 'accept') {
                $logger->info("Inside fraud accept");
                $payment->setIsFraudDetected(false);
                $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, null, true);
                $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PROCESSING, $order_note);
                $this->paymentOrderRepository->generateInvoice($order, $trxId);
            }
        } elseif ($transaction == 'settlement') {
            $logger->info("Transaction Settlement");
            $payment->setIsFraudDetected(false);
            $payment->setTransactionId($trxId);
            $payment->setIsTransactionClosed(true);
            $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, null, true);
            if ($payment_type != 'credit_card') {
                $logger->info("Payment type not credit card");
                $this->paymentOrderRepository->setPaymentInformation($order, $trxId, $payment_type);
                $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PROCESSING, $order_note);
                $this->paymentOrderRepository->generateInvoice($order, $trxId);
            }
        } elseif ($transaction == 'pending') {
            $logger->info("Transaction Pending");
            $this->paymentOrderRepository->setPaymentInformation($order, $trxId, $payment_type);
            $order_note = $note_prefix . 'Awaiting Payment - ' . $payment_type;
            $logger->info("Order Note: " . $order_note);
            $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PENDING_PAYMENT, $order_note);
        } elseif ($transaction == 'cancel') {
            $logger->info("Transaction Cancel");
            $order_note = $note_prefix . 'Canceled Payment - ' . $payment_type;
            $logger->info("Order Note: " . $order_note);
            // add to transaction menu record list if cancel req for status capture only
            if ($order->hasInvoices()) {
                $logger->info("Invoices cancelled");
                $payment = $order->getPayment();
                $payment->setParentTransactionId($trxId);
                $payment->setIsTransactionClosed(true);
                if (!PaymentUtils::isOpenApi($payment_type)){
                    $payment->setTransactionId($trxId . '-' . strtoupper($transaction));
                }
                $payment->addTransaction(TransactionInterface::TYPE_VOID, null, true);
            }
            $this->paymentOrderRepository->cancelOrder($order, Order::STATE_CANCELED, $order_note);
        } elseif ($transaction == 'expire') {
            $logger->info("Transaction Expire");
            if ($order->canCancel()) {
                $logger->info("Inside Cancelled");
                $order_note = $note_prefix . 'Expired Payment - ' . $payment_type;
                $logger->info("Order Note: " . $order_note);
                $this->paymentOrderRepository->cancelOrder($order, Order::STATE_CANCELED, $order_note);
            }
        } elseif ($transaction == 'deny') {
            $logger->info("Transaction Denied");
            $this->paymentOrderRepository->setPaymentInformation($order, $trxId, $payment_type);
            $order_note = $note_prefix . 'Payment Deny - ' . $payment_type;
            $logger->info("Order Note: " . $order_note);
            $this->paymentOrderRepository->setOrderStateAndStatus($order, Order::STATE_PAYMENT_REVIEW, $order_note);
        } elseif ($transaction == 'refund' || $transaction == 'partial_refund') {
            $logger->info("Transaction Refund or Partial Refund");
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
                    $orderRefund = $this->paymentOrderRepository->getOrderByIncrementId($midtransOrderId);
                    $this->processRefund($orderRefund, $refunds, true, $grossAmount);
                } else {
                    /**
                     * if order-id not found in reasons, handle as refund from MAP
                     */
                    $midtransOrderId = $midtransStatusResult->order_id;

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
        $_info = "status : " . $transaction . " , order : " . $midtransOrderId . ", payment type : " . $payment_type . "transaction id" . $trxId;
        $this->_midtransLogger->midtransNotification($_info);
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
    private function processRefund(Order $orderRefund, $refunds, $isFromMagento, $grossAmount = null)
    {
        $refund[] = end($refunds);
        $refundAmount = $refund[0]->refund_amount;
        $refund_reason = $refund[0]->reason;

        $isFullRefund = $this->isFullRefund($refunds, $orderRefund, $isFromMagento, $grossAmount);
        $refund_note = 'MIDTRANS NOTIFICATION | Refunded: ' . $refundAmount . '  |  Reason: ' . $refund_reason;

        /** Handling full refund */
        if ($isFullRefund && $orderRefund->getStatus() != Order::STATE_CLOSED && $orderRefund->getState() != Order::STATE_CLOSED) {
            $this->paymentOrderRepository->cancelOrder($orderRefund, Order::STATE_CLOSED, $refund_note);
        }
        /** Handling partial refund */
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
        }
        /**
        Skip refund process if not qualified
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
    private function isFullRefund(array $refunds, Order $order, $isFromMagento, $grossAmount = null)
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
            }/** for regular order */
            else {
                foreach ($refunds as $refund) {
                    $refundAmount += (double)$refund->refund_amount;
                }
            }
        }
        return $orderAmount === $refundAmount;
    }

    /**
     * Function to get Magento order-id from reasons refund,
     * the function used for request refund from Magento dashboard
     *
     * @param $refundReason
     * @return mixed|string|null
     */
    private function getOrderIdFromReason($refundReason)
    {
        $array = explode(":::", $refundReason);
        if (isset($array[1])) {
            return $array[1];
        } else {
            return null;
        }
    }

    /**
     * Function to check comment history notification is exist or not
     *
     * @param Order $order
     * @param $comment
     * @return bool
     */
    private function isOrderCommentExist(Order $order, $comment)
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
}
