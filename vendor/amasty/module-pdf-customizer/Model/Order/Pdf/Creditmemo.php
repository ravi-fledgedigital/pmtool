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

class Creditmemo extends \Magento\Framework\DataObject
{
    /**
     * @var PdfFactory
     */
    private $pdfFactory;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Html\Creditmemo
     */
    private $creditmemoHtml;

    /**
     * @var PdfLibProvider
     */
    private $pdfLibProvider;

    public function __construct(
        ?PdfFactory $pdfFactory, // @deprecated
        \Amasty\PDFCustom\Model\Order\Html\Creditmemo $creditmemoHtml,
        array $data = [],
        ?PdfLibProvider $pdfLibProvider = null // TODO move to not optional
    ) {
        $this->pdfFactory = $pdfFactory;
        $this->creditmemoHtml = $creditmemoHtml;
        $this->pdfLibProvider = $pdfLibProvider ?? ObjectManager::getInstance()->get(PdfLibProvider::class);
        parent::__construct($data);
    }

    /**
     * Return PDF document
     *
     * @param array|\Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection $creditmemos
     * @return \Amasty\PDFCustom\Model\PdfLib\PdfInterface
     */
    public function getPdf($creditmemos = [])
    {
        $pdf = $this->pdfLibProvider->get();
        $html = '';
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        foreach ($creditmemos as $creditmemo) {
            $html .= $this->creditmemoHtml->getHtml($creditmemo);
        }

        $pdf->setHtml($html);

        return $pdf;
    }
}
