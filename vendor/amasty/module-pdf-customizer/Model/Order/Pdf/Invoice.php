<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Order\Pdf;

use Amasty\PDFCustom\Model\PdfFactory;
use Amasty\PDFCustom\Model\PdfLib\PdfLibProvider;
use Magento\Framework\App\ObjectManager;

class Invoice extends \Magento\Framework\DataObject
{
    /**
     * @var PdfFactory
     */
    private $pdfFactory;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Html\Invoice
     */
    private $invoiceHtml;

    /**
     * @var PdfLibProvider
     */
    private $pdfLibProvider;

    public function __construct(
        ?PdfFactory $pdfFactory, // @deprecated
        \Amasty\PDFCustom\Model\Order\Html\Invoice $invoiceHtml,
        array $data = [],
        ?PdfLibProvider $pdfLibProvider = null // TODO move to not optional
    ) {
        $this->pdfFactory = $pdfFactory;
        $this->invoiceHtml = $invoiceHtml;
        $this->pdfLibProvider = $pdfLibProvider ?? ObjectManager::getInstance()->get(PdfLibProvider::class);
        parent::__construct($data);
    }

    /**
     * Return PDF document
     *
     * @param array|\Magento\Sales\Model\ResourceModel\Order\Invoice\Collection $invoices
     * @return \Amasty\PDFCustom\Model\PdfLib\PdfInterface
     */
    public function getPdf($invoices = [])
    {
        $pdf = $this->pdfLibProvider->get();
        $html = '';
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        foreach ($invoices as $invoice) {
            $html .= $this->invoiceHtml->getHtml($invoice);
        }

        $pdf->setHtml($html);

        return $pdf;
    }
}
