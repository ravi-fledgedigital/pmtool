<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Adminhtml\Order;

use Amasty\PDFCustom\Controller\Adminhtml\AbstractOrderMassAction;
use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Zip\PdfArchiveBuilderFactory;
use Amasty\PDFCustom\Model\Order\Pdf\Order;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class PdfOrders extends AbstractOrderMassAction
{
    /**
     * @var PdfArchiveBuilderFactory
     */
    private $pdfArchiveBuilderFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Order
     */
    private $pdfOrder;

    /**
     * @var Filter
     */
    private $filter;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        DateTime $dateTime,
        FileFactory $fileFactory,
        PdfArchiveBuilderFactory $pdfArchiveBuilderFactory,
        ConfigProvider $configProvider,
        Order $pdfOrder,
        Filter $filter
    ) {
        $this->pdfArchiveBuilderFactory = $pdfArchiveBuilderFactory;
        $this->collectionFactory = $collectionFactory;
        $this->fileFactory = $fileFactory;
        $this->dateTime = $dateTime;
        $this->configProvider = $configProvider;
        $this->pdfOrder = $pdfOrder;
        $this->filter = $filter;
        parent::__construct($context, $filter);
    }

    /**
     * @param AbstractCollection $collection
     *
     * @return ResponseInterface|ResultInterface
     */
    protected function massAction(AbstractCollection $collection)
    {
        $selectedIds = $collection->getAllIds();
        $ordersCollection = $this->collectionFactory->create()->addFieldToFilter('entity_id', ['in' => $selectedIds]);
        if ($ordersCollection->getSize() < 2) {
            $fileContent = $this->pdfOrder->getPdf([$ordersCollection->getLastItem()])->render();
            $fileName = 'order%s.pdf';
        } else {
            $pdfArchiveBuilder = $this->pdfArchiveBuilderFactory->create();
            $pdfArchiveBuilder->setOrdersCollection($ordersCollection);
            $zip = $pdfArchiveBuilder->build();
            $fileContent = $zip->render();
            $fileName = 'order%s.zip';
        }

        return $this->fileFactory->create(
            sprintf($fileName, $this->dateTime->date('Y-m-d_H-i-s')),
            $fileContent,
            DirectoryList::VAR_DIR,
            'application/zip'
        );
    }
}
