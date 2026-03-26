<?php

namespace OnitsukaTiger\Sales\Plugin;

use Magento\Sales\Model\Order\Shipment;

class ShipmentCreatedAtPlugin
{
    public function beforeSetCreatedAt(Shipment $subject, $date)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/shipment_created_at.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // Get shipment IDs (may be null if not set yet)
        $shipmentId = $subject->getId();
        $shipmentIncrementId = $subject->getIncrementId();

        // Extract last called class from backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $callerInfo = isset($trace[1]) ? $trace[1] : [];
        $lastCaller = isset($callerInfo['class']) ? $callerInfo['class'] : 'N/A';
        $lastFunction = isset($callerInfo['function']) ? $callerInfo['function'] : 'N/A';

        // Log
        $logger->info("==== setCreatedAt() Called ====");
        $logger->info("Shipment Entity ID: " . ($shipmentId ?? '[null]'));
        $logger->info("Shipment Increment ID: " . ($shipmentIncrementId ?? '[null]'));
        $logger->info("Shipment Created At: " . $date);
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
