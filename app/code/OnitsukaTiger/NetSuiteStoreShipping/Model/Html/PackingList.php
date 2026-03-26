<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Model\Html;

use Amasty\PDFCustom\Model\Order\Html\AbstractTemplate;
use Magento\Framework\DataObject;

/**
 * Class PackingList
 * @package OnitsukaTiger\NetSuiteStoreShipping\Model\Html
 */
class PackingList extends AbstractTemplate
{
    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     * @throws \Magento\Framework\Exception\MailException
     */
    public function getHtml($shipment)
    {
        $order = $shipment->getOrder();
        $templateId = $this->templateRepository->getPackingListTemplateId(
            $shipment->getStoreId(),
            $order->getCustomerGroupId()
        );

        if (!$templateId) {
            return '';
        }

        $shippingNumberTitle = '';
        $order = $this->orderRepository->get($shipment->getOrderId());
        if ($order->getShipmentsCollection()->count() != 1 && $shipment->getData('shipment_number')) {
            $shippingNumberTitle = '(Shipment ' . $shipment->getData('shipment_number') . ' of ' . $order->getShipmentsCollection()->count() . ')';
        }
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        $vars = [
            'order' => $order,
            'invoice' => $invoice,
            'shipment' => $shipment,
            'comment' => $shipment->getCustomerNoteNotify() ? $shipment->getCustomerNote() : '',
            'billing' => $order->getBillingAddress(),
            'created_at_formatted' => $order->getCreatedAtFormatted(2),
            'invoice_created_at_formatted' => $invoice->getCreatedAt(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'shipping_number'  => $shippingNumberTitle
        ];
        $transportObject = new DataObject($vars);
        $this->eventManager->dispatch(
            'packing_list_set_template_vars_before',
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
