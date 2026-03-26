<?php

namespace Seoulwebdesign\Base\Gateway\Response\Handler;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Class OrderStatus
 * @package Seoulwebdesign\Base\Gateway\Response\Handler\Authorize
 */
class SendOrderEmail implements HandlerInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * OrderStatus constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
s     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $handlingSubject['payment']->getPayment();
        /** @var $order Order */
        $order = $payment->getOrder();
        try {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/emailLogger.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Seoulwebdesign Base SendOrderEmail File Execute.');
            $this->orderSender->send($order, true);
        }catch (\Throwable $t) {
            $order->addCommentToStatusHistory(__('Failed send new order email'));
            $order->save();
        }
    }
}
