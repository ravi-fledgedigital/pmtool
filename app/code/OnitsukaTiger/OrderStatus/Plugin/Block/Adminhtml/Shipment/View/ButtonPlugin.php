<?php

namespace OnitsukaTiger\OrderStatus\Plugin\Block\Adminhtml\Shipment\View;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Shipping\Block\Adminhtml\View;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;

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
        $shipment = $subject->getShipment();
        $shipmentStatus = $shipment->getExtensionAttributes()->getStatus();
        if ($shipment->getStoreId() != \OnitsukaTiger\Store\Model\Store::KO_KR &&
            (
                $shipmentStatus == OrderStatus::STATUS_SHIPPED ||
                $shipmentStatus == OrderStatus::STATE_PACKED
            )
        ) {
            $message = __('Are you sure you want to recover this shipment?');
            if ($this->_authorization->isAllowed('Magento_Sales::ship_delivered')) {
                $subject->addButton(
                    'shipment-delivered',
                    [
                        'label' => __('Delivered'),
                        'onclick' => 'confirmSetLocation(\'' . $message . '\',\'' . $this->getDeliveredUrl($shipment->getEntityId(), $subject) . '\')',
                        'class' => 'shipment-delivered'
                    ],
                    -1
                );
            }
            if ($this->_authorization->isAllowed('Magento_Sales::ship_delivery_failed')) {
                $subject->addButton(
                    'shipment-delivery-failed',
                    [
                        'label' => __('Delivery Failed'),
                        'onclick' => 'confirmSetLocation(\'' . $message . '\',\'' . $this->getDeliveryFailedUrl($shipment->getEntityId(), $subject) . '\')',
                        'class' => 'shipment-delivery-failed'
                    ],
                    -1
                );
            }
        }
    }

    /**
     * @param $shipmentId
     * @param View $subject
     * @return string
     */
    private function getDeliveredUrl($shipmentId, View $subject): string
    {
        return $subject->getUrl(
            'order_status/recover/recoverDelivered',
            [
                'shipment_id' => $shipmentId
            ]
        );
    }

    /**
     * @param $shipmentId
     * @param View $subject
     * @return string
     */
    private function getDeliveryFailedUrl($shipmentId, View $subject): string
    {
        return $subject->getUrl(
            'order_status/recover/recoverDeliveryFailed',
            [
                'shipment_id' => $shipmentId
            ]
        );
    }
}
