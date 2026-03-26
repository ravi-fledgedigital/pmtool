<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Model\Pdf;

/**
 * Class PackingList
 * @package OnitsukaTiger\NetSuiteStoreShipping\Model\Pdf
 */
class PackingList extends \Magento\Framework\DataObject
{
    /**
     * @var \Amasty\PDFCustom\Model\PdfFactory
     */
    private $pdfFactory;

    /**
     * @var \OnitsukaTiger\NetSuiteStoreShipping\Model\Html\PackingList
     */
    private $packingListHtml;

    /**
     * InvoiceToWareHouse constructor.
     * @param \Amasty\PDFCustom\Model\PdfFactory $pdfFactory
     * @param \OnitsukaTiger\NetSuiteStoreShipping\Model\Html\PackingList $packingListHtml
     * @param array $data
     */
    public function __construct(
        \Amasty\PDFCustom\Model\PdfFactory $pdfFactory,
        \OnitsukaTiger\NetSuiteStoreShipping\Model\Html\PackingList $packingListHtml,
        array $data = []
    ) {
        $this->pdfFactory = $pdfFactory;
        $this->packingListHtml = $packingListHtml;
        parent::__construct($data);
    }

    /**
     * Return PDF document
     *
     * @param array|\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments
     * @return \Amasty\PDFCustom\Model\Pdf
     */
    public function getPdf($shipments = [])
    {
        /** @var \Amasty\PDFCustom\Model\Pdf $pdf */
        $pdf = $this->pdfFactory->create();
        $html = '';
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        foreach ($shipments as $shipment) {
            $html .= $this->packingListHtml->getHtml($shipment);
        }

        $pdf->setHtml($html);

        return $pdf;
    }
}
