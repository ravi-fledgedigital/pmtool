<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Order\Html;

use Magento\Framework\DataObject;

class Order extends AbstractTemplate
{
    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return string
     */
    public function getHtml($order)
    {
        $order = $this->orderRepository->get($order->getId());
        $order->setCreatedAt($this->getFormattedDate($order->getCreatedAt(), $order->getStoreId()));
        $templateId = $this->templateRepository->getOrderTemplateId($order->getStoreId(), $order->getCustomerGroupId());

        if (!$templateId) {
            return '';
        }

        $vars = [
            'order' => $order,
            'order_id' => $order->getId(),
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
            'email_order_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject, 'transportObject' => $transportObject]
        );

        $template = $this->templateFactory->get($templateId)
            ->setVars($transportObject->getData())
            ->setOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $order->getStoreId()
                ]
            );

        return $template->processTemplate();
    }
}
