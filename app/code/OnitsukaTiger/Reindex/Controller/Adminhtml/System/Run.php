<?php

namespace OnitsukaTiger\Reindex\Controller\Adminhtml\System;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Xml\Parser;
use OnitsukaTiger\Logger\Api\Logger as OnitsukaTigerLogger;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Filesystem;
use OnitsukaTiger\Reindex\Console\Command\Reindex;
use OnitsukaTiger\Reindex\Helper\Data;

/**
 * Class Run
 * @package OnitsukaTiger\Reindex\Controller\Adminhtml\System
 */
class Run extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var OnitsukaTigerLogger
     */
    protected $logger;

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Parser $parser
     * @param OnitsukaTigerLogger $logger
     * @param IndexerRegistry $indexerRegistry
     * @param Filesystem $filesystem
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Parser $parser,
        OnitsukaTigerLogger $logger,
        IndexerRegistry $indexerRegistry,
        Filesystem $filesystem,
        Data $dataHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->parser = $parser;
        $this->logger = $logger;
        $this->indexerRegistry = $indexerRegistry;
        $this->_filesystem = $filesystem;
        $this->dataHelper = $dataHelper;
        parent::__construct($context);
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $status = false;
        $message = __('Error! An error occurred. Please try again later <br> You can find out more in the error log.');
        try {
            $dir = $this->_filesystem->getDirectoryWrite(Data::DIR);
            $filePath = $dir->getAbsolutePath().Data::FILEPATH;

            $result = $this->parser->load($filePath)->xmlToArray();
            $listSku = array_unique($result['root']['Sku']);
            $productIds = $this->dataHelper->getProductIds($listSku);

            if (count($productIds) > 0) {
                foreach (Reindex::LIST_INDEXERS as $indexList) {
                    $indexer = $this->indexerRegistry->get($indexList);
                    $indexer->reindexList(array_unique($productIds));
                }
                $status  = true;
                $message = __('Reindex Done %1 product!', count($productIds));
            }else{
                $message = __('There are no products in sku\'s list');
            }

        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), []);
        }

        /** @var Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData(
            [
                'success' => $status,
                'message' => $message
            ]);
    }
}
