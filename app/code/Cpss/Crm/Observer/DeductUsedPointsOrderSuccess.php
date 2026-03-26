<?php

namespace Cpss\Crm\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class DeductUsedPointsOrderSuccess implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Cpss\Crm\Model\CpssApiRequest
     */
    protected $cpssApiRequest;

    /**
     * @var \Cpss\Crm\Helper\Customer
     */
    protected $customerHelper;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest
     * @param \Cpss\Crm\Helper\Customer $customerHelper
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param RequestInterface $request
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest,
        \Cpss\Crm\Helper\Customer $customerHelper,
        private \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        private \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        private \Magento\Framework\DB\Transaction $transaction,
        RequestInterface $request
    ) {
        $this->customerSession = $customerSession;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->customerHelper = $customerHelper;
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/order_success_or_fail.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("=========Logger start=========");
        if ($this->customerHelper->isModuleEnabled()) {

            $order = $observer->getEvent()->getOrder();
            $memberId = $this->customerSession->getMemberId();
            $usedPoint = $order->getUsedPoint();

            $code = $this->_request->getParam('code',false);

            //Sub Point
            if ($usedPoint > 0 && !$code) {
                $response = $this->cpssApiRequest->usePoint($order->getIncrementId(), $memberId, $usedPoint);
                $order->setCpssSubStatus($response['X-CPSS-Result']);
                $logger->info("Code is Empty");
            }
            $logger->info("Order Id : " . $order->getIncrementId());
            $logger->info("=========Logger END=========");
            $payment = $order->getPayment();
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/PlaceOrderAfter.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('==========================Payment Method Block Start============================');
            $logger->info('Payment Method Code: ' . $payment->getMethod());
            if ($payment->getMethod() == 'fullpoint' && $order->canInvoice()) {
                $logger->info('Inside if');
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);
                $invoice->save();
                $transactionSave = $this->transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
                $this->invoiceSender->send($invoice);
                $order->addStatusHistoryComment(
                    __('Invoice create for this order. Invoice ID: #%1.', $invoice->getId())
                )->save();
            }

        }

        return $this;
    }
}