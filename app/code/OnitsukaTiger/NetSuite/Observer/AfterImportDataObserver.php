<?php


namespace OnitsukaTiger\NetSuite\Observer;

class AfterImportDataObserver implements \Magento\Framework\Event\ObserverInterface
{
    const TARGET_JOB_TITLE = 'NetSuite Product Enable';

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;
    /**
     * @var Firebear\ImportExport\Model\ResourceModel\Job\Collection
     */
    protected $collectionFactory;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory $collectionFactory
    )
    {
        $this->productRepository = $productRepository;
        $this->publisher = $publisher;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AfterImportProductData.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        /* @type \Magento\CatalogImportExport\Model\Import\Product $adapter */
        $adapter = $observer->getEvent()->getAdapter();
        $separator = $adapter->getMultipleValueSeparator();
        $param = $adapter->getParameters();
        // accept only from firebear import
        if (array_key_exists('job_id', $param)) {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter($collection->getIdFieldName(), $param['job_id']);
            foreach ($collection as $job) {
                $title = $job->getTitle();
                if (self::TARGET_JOB_TITLE == $title) {
                    if ($products = $observer->getEvent()->getBunch()) {
                        foreach ($products as $data) {
                            $product = $this->productRepository->get($data['sku']);
                            $logger->info("---------------------- START Product Data(Before) ----------------------");
                            $logger->info("SKU: " . $product->getSku());
                            $logger->info("Enable: " . $product->getStatus());
                            $logger->info("---------------------- END Product Data(Before) ----------------------");

                            $message = new \OnitsukaTiger\NetSuite\Model\Queue\ProductMessage();
                            $message->setProductId($product->getEntityId());
                            $message->setNetsuiteWebTh(isset($data['netsuite_web_th']) ?? '');
                            $message->setNetsuiteWebMy(isset($data['netsuite_web_my']) ?? '');
                            $message->setNetsuiteWebSg(isset($data['netsuite_web_sg']) ?? '');
                            $message->setNetsuiteWebVn(isset($data['netsuite_web_vn']) ?? '');
                            $message->setRetry(0);
                            $this->publisher->publish(
                                \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface::TOPIC_NAME,
                                $message
                            );
                        }
                    }
                } else {
                    if ($products = $observer->getEvent()->getBunch()) {
                        foreach ($products as $data) {
                            $product = $this->productRepository->get($data['sku']);
                            if (!empty($product->getData())) {
                                $logger->info("---------------------- START Product Data(Before) ----------------------");
                                $logger->info("SKU: " . $product->getSku());
                                $logger->info("Enable: " . $product->getStatus());
                                $logger->info("---------------------- END Product Data(Before) ----------------------");
                            }
                        }
                    }
                }
            }
        }
    }
}
