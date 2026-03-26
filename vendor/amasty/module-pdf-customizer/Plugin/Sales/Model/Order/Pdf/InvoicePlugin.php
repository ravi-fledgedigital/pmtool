<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Sales\Model\Order\Pdf;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;

class InvoicePlugin
{
    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\InvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @var \Amasty\PDFCustom\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        \Amasty\PDFCustom\Model\Order\Pdf\InvoiceFactory $invoiceFactory,
        \Amasty\PDFCustom\Model\ConfigProvider $configProvider,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository
    ) {
        $this->invoiceFactory = $invoiceFactory;
        $this->configProvider = $configProvider;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @param \Magento\Sales\Model\Order\Pdf\Invoice $subject
     * @param callable $proceed
     * @param array $invoices
     *
     * @return \Zend_Pdf
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetPdf($subject, callable $proceed, $invoices = [])
    {
        if ($invoices instanceof Collection) {
            $invoice = $invoices->getFirstItem();
        } else {
            $invoice = current($invoices);
        }

        if (!$invoice) {
            return $proceed($invoices);
        }

        /** @var Order $order */
        $order = $invoice->getOrder();

        if (!$this->configProvider->isEnabled() ||
            $this->templateRepository->getInvoiceTemplateId($order->getStoreId(), $order->getCustomerGroupId()) == '0'
        ) {
            return $proceed($invoices);
        }

        /** @var \Amasty\PDFCustom\Model\Order\Pdf\Invoice $pdfRender */
        $pdfRender = $this->invoiceFactory->create();

        return $pdfRender->getPdf($invoices)->convertToZendPDF();
    }
}
