<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Zip;

use Amasty\PDFCustom\Model\ZipFactory;
use Amasty\PDFCustom\Model\Order\Pdf\Order;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\Order\Pdf\Creditmemo;
use Magento\Sales\Model\Order\Pdf\Invoice;
use Magento\Sales\Model\Order\Pdf\Shipment;

/**
 * Class PdfArchiveBuilder
 *
 * Builder for zip archive with orders, invoices, shipments and creditmemos
 */
class PdfArchiveBuilder
{
    /**
     * @var AbstractCollection
     */
    private $invoicesCollection;

    /**
     * @var AbstractCollection
     */
    private $shipmentsCollection;

    /**
     * @var AbstractCollection
     */
    private $creditmemosCollection;

    /**
     * @var AbstractCollection
     */
    private $ordersCollection;

    /**
     * @var Invoice
     */
    private $pdfInvoice;

    /**
     * @var Shipment
     */
    private $pdfShipment;

    /**
     * @var Creditmemo
     */
    private $pdfCreditmemo;

    /**
     * @var ZipFactory
     */
    private $zipFactory;

    /**
     * @var Order
     */
    private $pdfOrder;

    public function __construct(
        Invoice $pdfInvoice,
        Shipment $pdfShipment,
        Creditmemo $pdfCreditmemo,
        ZipFactory $zipFactory,
        Order $pdfOrder
    ) {
        $this->pdfInvoice = $pdfInvoice;
        $this->pdfShipment = $pdfShipment;
        $this->pdfCreditmemo = $pdfCreditmemo;
        $this->zipFactory = $zipFactory;
        $this->pdfOrder = $pdfOrder;
    }

    /**
     * @param AbstractCollection $collection
     * @return $this
     */
    public function setInvoicesCollection(AbstractCollection $collection)
    {
        $this->invoicesCollection = $collection;

        return $this;
    }

    /**
     * @param AbstractCollection $collection
     * @return $this
     */
    public function setOrdersCollection(AbstractCollection $collection)
    {
        $this->ordersCollection = $collection;

        return $this;
    }

    /**
     * @param AbstractCollection $collection
     * @return $this
     */
    public function setShipmentsCollection(AbstractCollection $collection)
    {
        $this->shipmentsCollection = $collection;

        return $this;
    }

    /**
     * @param AbstractCollection $collection
     * @return $this
     */
    public function setCreditmemosCollection(AbstractCollection $collection)
    {
        $this->creditmemosCollection = $collection;

        return $this;
    }

    /**
     * @return \Amasty\PDFCustom\Model\Zip
     * @throws \Zend_Pdf_Exception
     */
    public function build()
    {
        $zip = $this->zipFactory->create();
        if ($this->invoicesCollection) {
            foreach ($this->invoicesCollection as $invoice) {
                $zip->addFileFromString(
                    sprintf('invoice%s.pdf', $invoice->getIncrementId()),
                    $this->pdfInvoice->getPdf([$invoice])->render()
                );
            }
        }
        if ($this->shipmentsCollection) {
            foreach ($this->shipmentsCollection as $shipment) {
                $zip->addFileFromString(
                    sprintf('packingslip%s.pdf', $shipment->getIncrementId()),
                    $this->pdfShipment->getPdf([$shipment])->render()
                );
            }
        }
        if ($this->creditmemosCollection) {
            foreach ($this->creditmemosCollection as $creditmemo) {
                $zip->addFileFromString(
                    sprintf('creditmemo%s.pdf', $creditmemo->getIncrementId()),
                    $this->pdfCreditmemo->getPdf([$creditmemo])->render()
                );
            }
        }
        if ($this->ordersCollection) {
            foreach ($this->ordersCollection as $order) {
                $zip->addFileFromString(
                    sprintf('order%s.pdf', $order->getIncrementId()),
                    $this->pdfOrder->getPdf([$order])->render()
                );
            }
        }

        return $zip;
    }
}
