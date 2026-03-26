<?php

namespace OnitsukaTiger\Sales\Plugin;

use Magento\Sales\Model\Order;

class OrderCreatedAtPlugin
{
    public function beforeSetCreatedAt(Order $subject, $date)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/order_created_at.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // Get order IDs (may be null if not set yet)
        $orderId = $subject->getId();
        $incrementId = $subject->getIncrementId();

        // Extract last called class from backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $callerInfo = isset($trace[1]) ? $trace[1] : [];
        $lastCaller = isset($callerInfo['class']) ? $callerInfo['class'] : 'N/A';
        $lastFunction = isset($callerInfo['function']) ? $callerInfo['function'] : 'N/A';

        // Log
        $logger->info("==== setCreatedAt() Called ====");
        $logger->info("Order Entity ID: " . ($orderId ?? '[null]'));
        $logger->info("Increment ID: " . ($incrementId ?? '[null]'));
        $logger->info("Order Created At: " . $date);
        $logger->info("Created At Date: " . $date);
        $logger->info("Called From: " . $lastCaller . '::' . $lastFunction);
        $logger->info(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
        $logger->info("====================================================================\n");

        if ($subject->getCreatedAt()) {
            $logger->info("Skipped overwriting created_at as it's already set.");
            return [ $subject->getCreatedAt() ];
        }

        return [$date];
    }
}
