<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Override\Sales\Controller\Creditmemo;

use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Zip\PdfArchiveBuilderFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\Pdf\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class Pdfcreditmemos extends \Magento\Sales\Controller\Adminhtml\Creditmemo\Pdfcreditmemos
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
        Creditmemo $pdfCreditmemo,
        DateTime $dateTime,
        FileFactory $fileFactory,
        CollectionFactory $collectionFactory,
        PdfArchiveBuilderFactory $pdfArchiveBuilderFactory,
        ConfigProvider $configProvider
    ) {
        parent::__construct($context, $filter, $pdfCreditmemo, $dateTime, $fileFactory, $collectionFactory);
        $this->pdfArchiveBuilderFactory = $pdfArchiveBuilderFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * @param AbstractCollection $collection
     * @return ResponseInterface
     * @throws \Exception
     * @throws \Zend_Pdf_Exception
     */
    public function massAction(AbstractCollection $collection)
    {
        if (!$this->configProvider->isEnabled() || $collection->getSize() < 2) {
            return parent::massAction($collection);
        }
        $pdfArchiveBuilder = $this->pdfArchiveBuilderFactory->create();
        $pdfArchiveBuilder->setCreditmemosCollection($collection);
        $zip = $pdfArchiveBuilder->build();

        return $this->fileFactory->create(
            sprintf('creditmemo%s.zip', $this->dateTime->date('Y-m-d_H-i-s')),
            $zip->render(),
            DirectoryList::VAR_DIR,
            'application/zip'
        );
    }
}
