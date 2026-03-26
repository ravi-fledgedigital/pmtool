<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Override\Sales\Controller\Shipment;

use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Zip\PdfArchiveBuilderFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\Pdf\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class Pdfshipments extends \Magento\Sales\Controller\Adminhtml\Shipment\Pdfshipments
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
        DateTime $dateTime,
        FileFactory $fileFactory,
        Shipment $shipment,
        CollectionFactory $collectionFactory,
        PdfArchiveBuilderFactory $pdfArchiveBuilderFactory,
        ConfigProvider $configProvider
    ) {
        parent::__construct($context, $filter, $dateTime, $fileFactory, $shipment, $collectionFactory);
        $this->pdfArchiveBuilderFactory = $pdfArchiveBuilderFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * @param AbstractCollection $collection
     * @return \Magento\Sales\Controller\Adminhtml\Shipment\AbstractShipment\Pdfshipments|ResponseInterface
     * @throws \Exception
     */
    public function massAction(AbstractCollection $collection)
    {
        if (!$this->configProvider->isEnabled() || $collection->getSize() < 2) {
            return parent::massAction($collection);
        }
        $pdfArchiveBuilder = $this->pdfArchiveBuilderFactory->create();
        $pdfArchiveBuilder->setShipmentsCollection($collection);
        $zip = $pdfArchiveBuilder->build();

        return $this->fileFactory->create(
            sprintf('packingslip%s.zip', $this->dateTime->date('Y-m-d_H-i-s')),
            $zip->render(),
            DirectoryList::VAR_DIR,
            'application/zip'
        );
    }
}
