<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Order\Html;

use Magento\Framework\DataObject;

class Invoice extends AbstractTemplate
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return string
     */
    public function getHtml($invoice)
    {
        $order = $this->orderRepository->get($invoice->getOrderId());
        $order->setCreatedAt($this->getFormattedDate($order->getCreatedAt(), $order->getStoreId()));
        $invoice->setCreatedAt($this->getFormattedDate($invoice->getCreatedAt(), $invoice->getStoreId()));
        $templateId = $this->templateRepository->getInvoiceTemplateId(
            $invoice->getStoreId(),
            $order->getCustomerGroupId()
        );

        if (!$templateId) {
            return '';
        }

        $vars = [
            'order' => $order,
            'order_id' => $order->getId(),
            'invoice' => $invoice,
            'invoice_id' => $invoice->getId(),
            'comment' => $invoice->getCustomerNoteNotify() ? $invoice->getCustomerNote() : '',
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
            'email_invoice_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $template = $this->templateFactory->get($templateId)
            ->setVars($transportObject->getData())
            ->setOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $invoice->getStoreId()
                ]
            );

        return $template->processTemplate();
    }
}
