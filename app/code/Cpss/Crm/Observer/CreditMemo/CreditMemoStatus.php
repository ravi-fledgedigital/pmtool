<?php

namespace Cpss\Crm\Observer\CreditMemo;

use Magento\Framework\Event\Observer;

class CreditMemoStatus implements \Magento\Framework\Event\ObserverInterface
{
    const CLOSED = 'closed';
    const FULLPOINT_METHOD = 'fullpoint';

    public function execute(Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        if ($payment->getOrder()->getStatus() != self::CLOSED) {
            if ($payment->getOrder()->getPayment()->getMethod() == self::FULLPOINT_METHOD) {
                $partial = $this->checkIfPartial($payment);
                if (!$partial) {
                    $this->setStatus($payment, self::CLOSED);
                }
            }
        } else {
            $partial = $this->checkIfPartial($payment);
            if (!$partial) {
                $payment->getOrder()->setState(self::CLOSED);
            }
        }
    }

    public function setStatus($payment, $status)
    {                
        $payment->getOrder()->setStatus($status);
        $payment->getOrder()->setState($status);
        
        $histories = $payment->getOrder()->getStatusHistories();
        $latestHistoryComment = array_pop($histories);
        $latestHistoryComment->setStatus($status);
    }

    public function checkIfPartial($payment)
    {                
        foreach ($payment->getOrder()->getItems() as $item) {
            $qtyInvoiced = (int) $item->getQtyInvoiced();
            $qtyRefunded = (int) $item->getQtyRefunded();
            if ($qtyRefunded < $qtyInvoiced) {
                // partial refund if true
                return true;
            }
        }
        return false;
    }
}
