<?php
declare(strict_types=1);

namespace OnitsukaTiger\EmailTemplate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddInvoiceVarEmailObserver implements ObserverInterface{

    public function execute(Observer $observer)
    {
        $transport = $observer->getTransport();
        $transportObject = $observer->getData('transportObject');
        /** @var \Magento\Sales\Model\Order|null $order */
        $order = null;
        if (is_object($transport)) {
            $order = $transport->getOrder();
        } elseif (is_array($transport) && array_key_exists('order', $transport)) {
            $order = $transport['order'];
        }

        /** @var \Magento\Sales\Model\Order\Invoice|null $invoice */
        $invoice = null;
        if (is_object($transport)) {
            $invoice = $transport->getInvoice();
        } elseif (is_array($transport) && array_key_exists('invoice', $transport)) {
            $invoice = $transport['invoice'];
        }

        $vars = [
            'invoice_created_at_formatted' => $invoice->getCreatedAt(),
            'order_created_at_formatted' => $order->getCreatedAtFormatted(2),
        ];
        $transportObject->addData($vars);
    }
}
