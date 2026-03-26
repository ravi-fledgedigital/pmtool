<?php

namespace OnitsukaTiger\OrderStatus\Plugin\Block\Adminhtml\Order;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Model\Order;

class ButtonPlugin
{
    /**
     * @var AuthorizationInterface
     */
    private AuthorizationInterface $_authorization;

    public function __construct(
        AuthorizationInterface $authorization
    ) {
        $this->_authorization = $authorization;
    }

    /**
     * @param View $subject
     * @param LayoutInterface $layout
     */
    public function beforeSetLayout(View $subject, LayoutInterface $layout): void
    {
        $order = $subject->getOrder();
        if ($this->_authorization->isAllowed('Magento_Sales::recover_canceled')) {
            if ($order->getStatus() == Order::STATE_CANCELED || $order->getState() == Order::STATE_CANCELED) {
                $message = __('Are you sure you want to recover this order?');
                $subject->addButton(
                    'recover-canceled',
                    [
                        'label' => __('Recover Canceled'),
                        'onclick' => 'confirmSetLocation(\'' . $message . '\',\'' . $this->buildUrl($order->getEntityId(), $subject) . '\')',
                        'class' => 'recover-canceled'
                    ],
                    -1
                );
            }
        }
    }

    /**
     * @param $orderId
     * @param View $subject
     * @return string
     */
    private function buildUrl($orderId, View $subject): string
    {
        return $subject->getUrl(
            'order_status/recover/recoverCanceled',
            [
                'ord_id' => $orderId
            ]
        );
    }
}
