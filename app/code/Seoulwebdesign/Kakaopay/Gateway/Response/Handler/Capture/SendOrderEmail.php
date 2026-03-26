<?php

namespace Seoulwebdesign\Kakaopay\Gateway\Response\Handler\Capture;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Class OrderStatus
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
            $this->orderSender->send($order, true);
        } catch (\Throwable $t) {
            $order->addStatusToHistory(
                $order->getStatus(),
                __('Failed send new order email: '.$t->getMessage())
            );
        }
    }
}
