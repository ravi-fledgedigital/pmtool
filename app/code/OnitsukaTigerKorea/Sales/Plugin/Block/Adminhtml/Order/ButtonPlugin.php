<?php

namespace OnitsukaTigerKorea\Sales\Plugin\Block\Adminhtml\Order;

use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTigerKorea\Sales\Helper\Data;

class ButtonPlugin
{
    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param View $subject
     * @param LayoutInterface $layout
     */
    public function beforeSetLayout(View $subject, LayoutInterface $layout): void
    {
        $order = $subject->getOrder();
        if ($this->helper->isSalesEnabled($order->getStoreId())) {
            $message = __('Are you sure you want to Delivered this order?');
            $subject->addButton(
                'order-delivered',
                [
                    'label' => __('Delivered'),
                    'onclick' => 'confirmSetLocation(\'' . $message . '\',\'' . $this->getDeliveredUrl($order->getEntityId(), $subject) . '\')',
                    'class' => 'order-delivered'
                ],
                -1
            );
        }
    }

    /**
     * @param $orderId
     * @param View $subject
     * @return string
     */
    private function getDeliveredUrl($orderId, View $subject): string
    {
        return $subject->getUrl(
            'kr_sales/sales_order/delivered',
            [
                'ord_id' => $orderId
            ]
        );
    }
}
