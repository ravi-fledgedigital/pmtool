<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Sales\Block\Order\Invoice;

use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Source\LinkType;
use Magento\Customer\Model\Context;

class ItemsPlugin
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Sales\Model\Order\Invoice
     */
    private $currentInvoice;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        ConfigProvider $configProvider,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Registry $registry,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository
    ) {
        $this->configProvider = $configProvider;
        $this->httpContext = $httpContext;
        $this->registry = $registry;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @param \Magento\Sales\Block\Order\Invoice\Items $subject
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     */
    public function beforeGetPrintInvoiceUrl(\Magento\Sales\Block\Order\Invoice\Items $subject, $invoice)
    {
        $this->currentInvoice = $invoice;
    }

    /**
     * @param \Magento\Sales\Block\Order\Invoice\Items $subject
     * @param string $result
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetPrintInvoiceUrl(\Magento\Sales\Block\Order\Invoice\Items $subject, $result)
    {
        $order = $subject->getOrder();
        if (!$this->isEnabledLinkReplace($order)) {
            return $result;
        }

        if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $subject->getUrl('custompdf/guest/invoice', ['invoice_id' => $this->currentInvoice->getId()]);
        }

        return $subject->getUrl('custompdf/sales/invoice', ['invoice_id' => $this->currentInvoice->getId()]);
    }

    /**
     * @param \Magento\Sales\Block\Order\Invoice\Items $subject
     * @param string $result
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetPrintAllInvoicesUrl(\Magento\Sales\Block\Order\Invoice\Items $subject, $result)
    {
        $order = $subject->getOrder();
        if (!$this->isEnabledLinkReplace($order)) {
            return $result;
        }

        if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $subject->getUrl('custompdf/guest/invoice', ['order_id' => $order->getId()]);
        }
        return $subject->getUrl('custompdf/sales/invoice', ['order_id' => $order->getId()]);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    private function isEnabledLinkReplace($order)
    {
        $storeId = $order->getStoreId();
        $customerGroupId = $order->getCustomerGroupId();
        if (!$this->configProvider->isEnabled($storeId) ||
            !$this->templateRepository->getInvoiceTemplateId($storeId, $customerGroupId)
        ) {
            return false;
        }
        $invoiceTypeLink = $this->configProvider->getInvoiceLinkType($storeId);

        return $invoiceTypeLink == LinkType::TYPE_REPLACE;
    }
}
