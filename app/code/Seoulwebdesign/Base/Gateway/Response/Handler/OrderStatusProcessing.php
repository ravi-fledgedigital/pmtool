<?php

namespace Seoulwebdesign\Base\Gateway\Response\Handler;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderStatus
 * @package Seoulwebdesign\Base\Gateway\Response\Handler\Authorize
 */
class OrderStatusProcessing implements HandlerInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * OrderStatus constructor.
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $handlingSubject['payment']->getPayment();
        /** @var $order \Magento\Sales\Model\Order */
        $order = $payment->getOrder();

//        $order->setState($this->paymentStatus->getOrderStateByPaymentStatus(PaymentStatus::PAYMENT_AUTHORIZED))
//            ->setStatus(PaymentStatus::PAYMENT_AUTHORIZED);
        $order->setCanSendNewEmailFlag(true);
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        //$order->addStatusToHistory($order->getStatus(), 'Payment Captured.');
        $this->orderRepository->save($order);
    }
}
