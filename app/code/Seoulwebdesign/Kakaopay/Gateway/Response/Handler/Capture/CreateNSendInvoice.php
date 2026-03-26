<?php

namespace Seoulwebdesign\Kakaopay\Gateway\Response\Handler\Capture;

use Magento\Framework\DB\Transaction;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * Class OrderStatus
 */
class CreateNSendInvoice implements HandlerInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var Transaction
     */
    protected $dbTransaction;
    /**
     * @var InvoiceService
     */
    protected $invoiceService;
    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * OrderStatus constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param Transaction $dbTransaction
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Transaction $dbTransaction,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender
    ) {
        $this->orderRepository = $orderRepository;
        $this->dbTransaction = $dbTransaction;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $handlingSubject['payment']->getPayment();
        /** @var $order Order */
        $order = $payment->getOrder();
        $this->createInvoice($order, $response['object']['tid']);
    }

    /**
     * @param Order $order
     * @param $transactionId
     * @return InvoiceInterface|false
     */
    public function createInvoice(Order $order, $transactionId)
    {
        if ($order->canInvoice()) {
            try {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setTransactionId($transactionId);
                $invoice->register();
                $invoice->pay();
                $invoice->save();
                $transactionSave = $this->dbTransaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
                try {
                    $this->invoiceSender->send($invoice);
                    //send notification code
                    $order->addStatusToHistory(
                        $order->getStatus(),
                        __('Notified customer about invoice #%1.', $invoice->getId())
                    );
                } catch (\Throwable $t) {
                    $order->addStatusToHistory(
                        $order->getStatus(),
                        __('Failed to sent the invoice #%1', $invoice->getId())
                    );
                }
                return $invoice;
            } catch (\Throwable $t) {
                $order->addStatusToHistory(
                    $order->getStatus(),
                    __('Failed create invoice: %1', $t->getMessage())
                );
                return false;
            }
        }
        return false;
    }
}
