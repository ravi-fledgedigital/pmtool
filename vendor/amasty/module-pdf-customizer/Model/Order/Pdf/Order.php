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

class Order extends \Magento\Framework\DataObject
{
    /**
     * @var \Amasty\PDFCustom\Model\PdfFactory
     */
    private $pdfFactory;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Html\Order
     */
    private $orderHtml;

    /**
     * @var PdfLibProvider
     */
    private $pdfLibProvider;

    public function __construct(
        ?PdfFactory $pdfFactory, // @deprecated
        \Amasty\PDFCustom\Model\Order\Html\Order $orderHtml,
        array $data = [],
        ?PdfLibProvider $pdfLibProvider = null // TODO move to not optional
    ) {
        $this->pdfFactory = $pdfFactory;
        $this->orderHtml = $orderHtml;
        $this->pdfLibProvider = $pdfLibProvider ?? ObjectManager::getInstance()->get(PdfLibProvider::class);
        parent::__construct($data);
    }

    /**
     * Return PDF document
     *
     * @param array|\Magento\Sales\Model\ResourceModel\Order\Collection $orders
     * @return \Amasty\PDFCustom\Model\PdfLib\PdfInterface
     */
    public function getPdf($orders = [])
    {
        $pdf = $this->pdfLibProvider->get();
        $html = '';
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $html .= $this->orderHtml->getHtml($order);
        }

        $pdf->setHtml($html);

        return $pdf;
    }
}
