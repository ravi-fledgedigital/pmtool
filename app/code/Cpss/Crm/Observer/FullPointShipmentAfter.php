<?php
namespace Cpss\Crm\Observer;

use Magento\Sales\Model\Service\InvoiceService;
use Cpss\Crm\Model\Fullpoint;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use \Psr\Log\LoggerInterface;

class FullPointShipmentAfter implements \Magento\Framework\Event\ObserverInterface
{
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;
    protected $session;
    protected $transactionRepository;
    protected $loggerInterface;

    public function __construct(
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction,
        \Magento\Backend\Model\Session $session,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        LoggerInterface $loggerInterface
    ) {
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->session = $session;
        $this->transactionRepository = $transactionRepository;
        $this->loggerInterface = $loggerInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();
        $payment = $order->getPayment();

        if ($payment->getMethod() == FullPoint::PAYMENT_METHOD_FULLPOINT_CODE && $order->canInvoice()) {
            try {
                $this->createInvoice($order, \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            } catch (\Exception $e) {
                $this->loggerInterface->critical($e->getMessage());
            }
        }
    }

    public function createInvoice($order, $captureCase, $txnID = null, $state = null)
    {
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase($captureCase);

        if ($txnID) $invoice->setTransactionId($txnID);

        if ($state) $invoice->setState($state);

        $invoice->register();
        $invoice->save();
        $transactionSave = $this->transaction->addObject(
            $invoice
        )->addObject(
            $invoice->getOrder()
        );
        $transactionSave->save();
        $this->invoiceSender->send($invoice);
    }
}