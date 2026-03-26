<?php
/**
 * phpcs:ignoreFile
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerIndo\Midtrans\Plugin\Controller\Payment;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Registry;
use Midtrans\Snap\Controller\Payment\Notification;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\TransactionInterface;

class NotificationPlugin
{
    /**
     * Before Processorder Plugin
     *
     * @param      \Midtrans\Snap\Controller\Payment\Notification  $subject               The subject
     * @param      \Magento\Sales\Model\Order                      $order                 The order
     * @param      <type>                                          $midtransStatusResult  The midtrans status result
     * @param      <type>                                          $rawBody               The raw body
     *
     * @return     <type>                                          ( description_of_the_return_value )
     */
    public function beforeProcessOrder(Notification $subject, Order $order, $midtransStatusResult, $rawBody)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get(\Psr\Log\LoggerInterface::class);
        $logger->info("==============midtrans beforeProcessOrder plugin==============");

        $midtransOrderId = $midtransStatusResult->order_id;
        $grossAmount = $midtransStatusResult->gross_amount;
        $transaction = $midtransStatusResult->transaction_status;
        $fraud = $midtransStatusResult->fraud_status;
        $payment_type = $midtransStatusResult->payment_type;
        $trxId = $midtransStatusResult->transaction_id;

        $logger->info("midtransOrderId = " . $midtransOrderId);
        $logger->info("grossAmount = " . $grossAmount);
        $logger->info("transaction = " . $transaction);
        $logger->info("fraud = " . $fraud);
        $logger->info("payment_type = " . $payment_type);
        $logger->info("trxId = " . $trxId);

        if ($transaction == 'deny') {
            if ($payment_type == 'credit_card') {
                $midtransStatusResult->transaction_status = Order::STATE_CANCELED;
                $logger->info("updated transaction status = " . $midtransStatusResult->transaction_status);
                $logger->info("midtransStatusResult : " . print_r($midtransStatusResult, true));
                return $midtransStatusResult;
            }
        }
    }
}
