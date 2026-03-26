<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Override\Sales\Controller;

use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Zip\PdfArchiveBuilderFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\Pdf\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class Pdfcreditmemos extends \Magento\Sales\Controller\Adminhtml\Order\Pdfcreditmemos
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
        CollectionFactory $collectionFactory,
        DateTime $dateTime,
        FileFactory $fileFactory,
        Creditmemo $pdfCreditmemo,
        PdfArchiveBuilderFactory $pdfArchiveBuilderFactory,
        ConfigProvider $configProvider
    ) {
        parent::__construct($context, $filter, $collectionFactory, $dateTime, $fileFactory, $pdfCreditmemo);
        $this->pdfArchiveBuilderFactory = $pdfArchiveBuilderFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * Print credit memos for selected orders
     *
     * @param AbstractCollection $collection
     * @return ResponseInterface|ResultInterface
     * @throws \Exception
     */
    protected function massAction(AbstractCollection $collection)
    {
        $creditmemoCollection = $this->collectionFactory->create()->setOrderFilter(['in' => $collection->getAllIds()]);

        if (!$this->configProvider->isEnabled() || $creditmemoCollection->getSize() < 2) {
            return parent::massAction($collection);
        }

        $pdfArchiveBuilder = $this->pdfArchiveBuilderFactory->create();
        $pdfArchiveBuilder->setCreditmemosCollection($creditmemoCollection);
        $zip = $pdfArchiveBuilder->build();

        return $this->fileFactory->create(
            sprintf('creditmemo%s.zip', $this->dateTime->date('Y-m-d_H-i-s')),
            $zip->render(),
            DirectoryList::VAR_DIR,
            'application/zip'
        );
    }
}
