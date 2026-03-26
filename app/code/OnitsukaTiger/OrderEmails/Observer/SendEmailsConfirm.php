<?php

namespace OnitsukaTiger\OrderEmails\Observer;

use Magento\Framework\Event\Observer;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use \OnitsukaTiger\Sales\Helper\Data;
use \Magento\Customer\Model\Session;

class SendEmailsConfirm implements \Magento\Framework\Event\ObserverInterface{

    /**
     * @var Data
     */
    protected $mailSender;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var OrderResource
     */
    protected $orderResource;

    protected $orderRepository;

    public function __construct(
        Data $mailSender,
        Session $customerSession,
        OrderResource $orderResource,
        OrderRepositoryInterface $orderRepository,
        private \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->customerSession = $customerSession;
        $this->mailSender = $mailSender;
        $this->orderResource = $orderResource;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();

        /** @var \Magento\Sales\Model\Order $order */
        $orderInVoice = $invoice->getOrder();
        $order = $this->orderRepository->get($orderInVoice->getEntityId());

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/emailConfirmation.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Send Email Confirmation Before Logger Start============================');
        $logger->info('Order ID: ' . $order->getIncrementId());
        $currentTime = date('Y-m-d H:i:s');
        $logger->info('Time Before: ' . $currentTime);
        $logger->info('==========================Send Email Confirmation Before Logger End============================');

        $invoiceCreatedAt = $invoice->getData('created_at');
        $currentDate = date('Y-m-d H:i:s');
        $hourdiff = round((strtotime($currentDate) - strtotime($invoiceCreatedAt))/3600, 1);

        if ($order->getEmailSent() || $invoice->getEmailSent() || $hourdiff > 5) {
            return $this;
        }
        $order->setEmailSent(true);
        $this->orderResource->saveAttribute($order, ['email_sent']);

        $connection  = $this->resourceConnection->getConnection();
        $tName = $connection->getTableName('sales_invoice');

        $sq = "UPDATE $tName SET `email_sent` = 1, `send_email` = 1 WHERE $tName.`entity_id` = " . $invoice->getId();
        $connection->query($sq);
        $updatedTime = date('Y-m-d H:i:s');
        $logger->info('==========================Send Email Confirmation After Logger Start============================');
        $logger->info('Time After: ' . $updatedTime);
        $logger->info('==========================Send Email Confirmation After Logger End============================');

        // Send Email Confirm
        if ($this->customerSession->isLoggedIn()) {
            // send email confirm with acc login
            $this->mailSender->sendConfirmEmailTemplate($orderInVoice);
        } else {
            // send email confirm with acc guest
            $this->mailSender->sendConfirmEmailGuestTemplate($orderInVoice);
        }
    }
}
