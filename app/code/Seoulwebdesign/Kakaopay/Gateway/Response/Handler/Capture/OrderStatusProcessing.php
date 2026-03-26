<?php

namespace Seoulwebdesign\Kakaopay\Gateway\Response\Handler\Capture;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderStatus
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
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $handlingSubject['payment']->getPayment();
        /** @var $order \Magento\Sales\Model\Order */
        $order = $payment->getOrder();
        try {
            $order->setCanSendNewEmailFlag(true);
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $this->orderRepository->save($order);
            $order->addStatusToHistory(
                $order->getStatus(),
                'Order status updated to processing.'
            );
        } catch (\Throwable $throwable) {
            $order->addStatusToHistory(
                $order->getStatus(),
                __('Failed update the Order status: '.$throwable->getMessage())
            );
        }
    }
}
