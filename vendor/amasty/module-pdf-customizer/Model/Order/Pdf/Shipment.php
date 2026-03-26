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

class Shipment extends \Magento\Framework\DataObject
{
    /**
     * @var \Amasty\PDFCustom\Model\PdfFactory
     */
    private $pdfFactory;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Html\Shipment
     */
    private $shipmentHtml;

    /**
     * @var PdfLibProvider
     */
    private $pdfLibProvider;

    public function __construct(
        ?PdfFactory $pdfFactory, // @deprecated
        \Amasty\PDFCustom\Model\Order\Html\Shipment $shipmentHtml,
        array $data = [],
        ?PdfLibProvider $pdfLibProvider = null // TODO move to not optional
    ) {
        $this->pdfFactory = $pdfFactory;
        $this->shipmentHtml = $shipmentHtml;
        $this->pdfLibProvider = $pdfLibProvider ?? ObjectManager::getInstance()->get(PdfLibProvider::class);
        parent::__construct($data);
    }

    /**
     * Return PDF document
     *
     * @param array|\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments
     * @return \Amasty\PDFCustom\Model\PdfLib\PdfInterface
     */
    public function getPdf($shipments = [])
    {
        $pdf = $this->pdfLibProvider->get();
        $html = '';
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        foreach ($shipments as $shipment) {
            $html .= $this->shipmentHtml->getHtml($shipment);
        }

        $pdf->setHtml($html);

        return $pdf;
    }
}
