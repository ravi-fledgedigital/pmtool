<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Order\Html;

use Magento\Framework\DataObject;

class Shipment extends AbstractTemplate
{
    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return string
     */
    public function getHtml($shipment)
    {
        $order = $this->orderRepository->get($shipment->getOrderId());
        $order->setCreatedAt($this->getFormattedDate($order->getCreatedAt(), $order->getStoreId()));
        $shipment->setCreatedAt($this->getFormattedDate($shipment->getCreatedAt(), $shipment->getStoreId()));
        $templateId = $this->templateRepository->getShipmentTemplateId(
            $order->getStoreId(),
            $order->getCustomerGroupId()
        );

        if (!$templateId) {
            return '';
        }

        $vars = [
            'order' => $order,
            'order_id' => $order->getId(),
            'shipment' => $shipment,
            'shipment_id' => $shipment->getId(),
            'comment' => $shipment->getCustomerNoteNotify() ? $shipment->getCustomerNote() : '',
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'orderHistoryComments' => $this->getFormattedOrderHistoryComments($order),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ]
        ];
        $transportObject = new DataObject($vars);
        $this->eventManager->dispatch(
            'email_shipment_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $template = $this->templateFactory->get($templateId)
            ->setVars($transportObject->getData())
            ->setOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $shipment->getStoreId()
                ]
            );

        return $template->processTemplate();
    }
}
