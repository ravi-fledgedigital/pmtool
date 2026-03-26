<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model;

class PdfProvider
{
    /**
     * @var Pdf[]
     */
    protected $invoicePdfStorage = [];
    /**
     * @var Pdf[]
     */
    protected $orderPdfStorage = [];

    /**
     * @var Pdf[]
     */
    protected $shipmentPdfStorage = [];

    /**
     * @var Pdf[]
     */
    private $creditmemoPdfStorage = [];

    /**
     * @var Order\Pdf\Invoice
     */
    private $invoicePdf;

    /**
     * @var Order\Pdf\Order
     */
    private $orderPdf;

    /**
     * @var Order\Pdf\Shipment
     */
    private $shipmentPdf;

    /**
     * @var Order\Pdf\Creditmemo
     */
    private $creditmemoPdf;

    public function __construct(
        \Amasty\PDFCustom\Model\Order\Pdf\Invoice $invoicePdf,
        \Amasty\PDFCustom\Model\Order\Pdf\Order $orderPdf,
        \Amasty\PDFCustom\Model\Order\Pdf\Shipment $shipmentPdf,
        \Amasty\PDFCustom\Model\Order\Pdf\Creditmemo $creditmemoPdf
    ) {
        $this->invoicePdf = $invoicePdf;
        $this->orderPdf = $orderPdf;
        $this->shipmentPdf = $shipmentPdf;
        $this->creditmemoPdf = $creditmemoPdf;
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice $saleObject
     *
     * @return Pdf
     */
    public function getInvoicePdf(\Magento\Sales\Model\AbstractModel $saleObject)
    {
        if (!isset($this->invoicePdfStorage[$saleObject->getId()])) {
            $this->invoicePdfStorage[$saleObject->getId()] = $this->invoicePdf->getPdf([$saleObject]);
        }

        return $this->invoicePdfStorage[$saleObject->getId()];
    }

    /**
     * @param \Magento\Sales\Model\Order $saleObject
     *
     * @return Pdf
     */
    public function getOrderPdf(\Magento\Sales\Model\AbstractModel $saleObject)
    {
        if (!isset($this->orderPdfStorage[$saleObject->getId()])) {
            $this->orderPdfStorage[$saleObject->getId()] = $this->orderPdf->getPdf([$saleObject]);
        }

        return $this->orderPdfStorage[$saleObject->getId()];
    }

    /**
     * @param \Magento\Sales\Model\Order $saleObject
     *
     * @return Pdf
     */
    public function getShipmentPdf(\Magento\Sales\Model\AbstractModel $saleObject)
    {
        if (!isset($this->shipmentPdfStorage[$saleObject->getId()])) {
            $this->shipmentPdfStorage[$saleObject->getId()] = $this->shipmentPdf->getPdf([$saleObject]);
        }

        return $this->shipmentPdfStorage[$saleObject->getId()];
    }

    /**
     * @param \Magento\Sales\Model\Order $saleObject
     *
     * @return Pdf
     */
    public function getCreditmemoPdf(\Magento\Sales\Model\AbstractModel $saleObject)
    {
        if (!isset($this->creditmemoPdfStorage[$saleObject->getId()])) {
            $this->creditmemoPdfStorage[$saleObject->getId()] = $this->creditmemoPdf->getPdf([$saleObject]);
        }

        return $this->creditmemoPdfStorage[$saleObject->getId()];
    }
}
