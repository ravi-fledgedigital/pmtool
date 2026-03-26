<?php

namespace OnitsukaTiger\PreOrders\Preference\Midtrans\Snap\Model\Order;

use Magento\Framework\DB\Transaction;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\CreditmemoRepository;
use Magento\Sales\Model\OrderRepository as MagentoOrderRepository;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Model\Service\InvoiceService;
use Midtrans\Snap\Logger\MidtransLogger;

class OrderRepository extends \Midtrans\Snap\Model\Order\OrderRepository
{
    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;


    public function __construct(
        Order $order,
        ObjectManagerInterface $objectManager,
        MagentoOrderRepository $magentoOrderRepository,
        MidtransLogger $midtransLogger,
        Order\Invoice $invoice,
        InvoiceService $invoiceService,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        CreditmemoRepository $creditmemoRepository,
        Transaction $transaction,
        MessageManagerInterface $messageManager,
        InvoiceRepositoryInterface $invoiceRepository
    ) {
        parent::__construct(
            $order,
            $objectManager,
            $magentoOrderRepository,
            $midtransLogger,
            $invoice,
            $invoiceService,
            $creditmemoFactory,
            $creditmemoService,
            $creditmemoRepository,
            $transaction,
            $messageManager,
            $invoiceRepository
        );
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * Do generate Invoice
     *
     * @param Order $order
     * @return InvoiceInterface|Order\Invoice
     */
    public function generateInvoice(Order $order, $midtransTrxId)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/OrderRepository.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Order Data Debugging Start============================');

        try {
            if ($order->isEmpty()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('MIDTRANS-INFO: The order no longer exists.'));
            }
            if (!$order->canInvoice()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('MIDTRANS-INFO: The order does not allow an invoice to be created.')
                );
            }

            $invoice = $this->invoiceService->prepareInvoice($order);
            if (!$invoice) {
                throw new \Magento\Framework\Exception\LocalizedException(__('MIDTRANS-INFO: We can\'t save the invoice right now.'));
            }
            if (!$invoice->getTotalQty()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('MIDTRANS-INFO: You can\'t create an invoice without products.'));
            }

            if ($midtransTrxId) {
                $invoice->setTransactionId($midtransTrxId);
                $order->getPayment()->setLastTransId($midtransTrxId);
            }
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            //  $invoice->pay();
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $logger->info("Order Id : ".$order->getId());
            $logger->info("Before Order Status : ".$order->getStatus());
            $logger->info("Before Order State : ".$order->getState());
            $this->invoiceRepository->save($invoice);
            $order->setState("processing")->setStatus("processing");
            $this->magentoOrderRepository->save($order);
            $logger->info("After Order Status : ".$order->getStatus());
            $logger->info("After Order State : ".$order->getState());
            $logger->info('==========================Order Data Debugging End============================');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
