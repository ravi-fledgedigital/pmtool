<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Override\Sales\Controller;

use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Zip\PdfArchiveBuilderFactory;
use Amasty\PDFCustom\Model\ZipFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Pdf\Creditmemo;
use Magento\Sales\Model\Order\Pdf\Shipment;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class Pdfdocs extends \Magento\Sales\Controller\Adminhtml\Order\Pdfdocs
{
    /**
     * @var PdfArchiveBuilderFactory
     */
    private $pdfArchiveBuilderFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Context $context,
        Filter $filter,
        FileFactory $fileFactory,
        \Magento\Sales\Model\Order\Pdf\Invoice $pdfInvoice,
        Shipment $pdfShipment,
        Creditmemo $pdfCreditmemo,
        DateTime $dateTime,
        ShipmentCollectionFactory $shipmentCollectionFactory,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        CreditmemoCollectionFactory $creditmemoCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        PdfArchiveBuilderFactory $pdfArchiveBuilderFactory,
        ConfigProvider $configProvider
    ) {
        parent::__construct(
            $context,
            $filter,
            $fileFactory,
            $pdfInvoice,
            $pdfShipment,
            $pdfCreditmemo,
            $dateTime,
            $shipmentCollectionFactory,
            $invoiceCollectionFactory,
            $creditmemoCollectionFactory,
            $orderCollectionFactory
        );
        $this->pdfArchiveBuilderFactory = $pdfArchiveBuilderFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * Override for fix page merging
     * Print all documents for selected orders
     *
     * @param AbstractCollection $collection
     *
     * @return ResponseInterface|\Magento\Backend\Model\View\Result\Redirect
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function massAction(AbstractCollection $collection)
    {
        if (!$this->configProvider->isEnabled()) {
            return parent::massAction($collection);
        }
        $orderIds = $collection->getAllIds();

        $shipments = $this->shipmentCollectionFactory->create()->setOrderFilter(['in' => $orderIds]);
        $invoices = $this->invoiceCollectionFactory->create()->setOrderFilter(['in' => $orderIds]);
        $creditmemos = $this->creditmemoCollectionFactory->create()->setOrderFilter(['in' => $orderIds]);
        $orders = $this->collectionFactory->create()->addFieldToFilter('entity_id', ['in' => $orderIds]);

        $pdfArchiveBuilder = $this->pdfArchiveBuilderFactory->create();
        $pdfArchiveBuilder->setInvoicesCollection($invoices)
            ->setShipmentsCollection($shipments)
            ->setCreditmemosCollection($creditmemos)
            ->setOrdersCollection($orders);
        $zip = $pdfArchiveBuilder->build();

        if (!$zip->countFiles()) {
            $this->messageManager->addError(__('There are no printable documents related to selected orders.'));

            return $this->resultRedirectFactory->create()->setPath($this->getComponentRefererUrl());
        }

        return $this->fileFactory->create(
            sprintf('docs%s.zip', $this->dateTime->date('Y-m-d_H-i-s')),
            $zip->render(),
            DirectoryList::VAR_DIR,
            'application/zip'
        );
    }
}
