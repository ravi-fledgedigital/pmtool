<?php

namespace Seoulwebdesign\Kakaopay\Model;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Psr\Log\LoggerInterface;

class OrderProcessing
{
    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;
    /**
     * @var OrderSender
     */
    protected $orderSender;
    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var OrderResource
     */
    protected $orderResource;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param InvoiceSender $invoiceSender
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param OrderSender $orderSender
     */
    public function __construct(
        LoggerInterface $logger,
        InvoiceSender $invoiceSender,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        OrderSender $orderSender
    ) {
        $this->logger = $logger;
        $this->invoiceSender = $invoiceSender;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->orderResource = $orderResource;
    }


    /**
     * @param $incrementId
     * @return Order
     */
    public function getOrderByIncrementId($incrementId)
    {
        $orderModel = $this->orderFactory->create();
        $this->orderResource->load($orderModel, $incrementId, 'increment_id');

        return $orderModel;
    }

    /**
     * @param Order $order
     * @return void
     */
    public function forceSendEmail(Order $order)
    {
        $this->orderSender->send($order, true);
    }

    public function sendInvoice($invoice)
    {
        //if ($this->configHelper->getPaymentConfig('can_send_invoice')) {
            $this->invoiceSender->send($invoice);
        //}
    }
}
