<?php


namespace OnitsukaTiger\NetSuite\Model\Handler;

class Product
{
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct
     */
    protected $enable;
    /**
     * @var \OnitsukaTiger\Logger\Api\Logger
     */
    protected $logger;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \OnitsukaTiger\Logger\Api\Logger $logger,
        \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct $enable
    )
    {
        $this->publisher = $publisher;
        $this->productRepository = $productRepository;
        $this->enable = $enable;
        $this->logger = $logger;
    }

    public function process(
        \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface $message
    )
    {
        $product = $this->productRepository->getById($message->getProductId(), false, null, true);
        $this->logger->info(sprintf('process SKU : %s', $product->getSku()));
        try {
            // merge with NetSuite value if value has null
            $product = $this->enable->mergeNetSuiteValue(
                $product->getSku(),
                $message->getNetsuiteWebTh(),
                $message->getNetsuiteWebMy(),
                $message->getNetsuiteWebSg(),
                $message->getNetsuiteWebVn()
            );

            // update NetSuite setting
            $this->enable->updateItem($product);
        } catch (\Exception $e) {
            if($message->getRetry() < 3) {
                $this->logger->err(sprintf('retry : %s error : %s', $message->getRetry() + 1, $e->getMessage()));
                $message->setRetry($message->getRetry() + 1);
                $this->publisher->publish(
                    \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface::TOPIC_NAME,
                    $message
                );
//                $retry = new \OnitsukaTiger\NetSuite\Model\Handler\Retry($this->publisher, $message);
//                $retry->start();
                return;
            }
            $this->logger->err(sprintf('retry failed : %s', $e->getMessage()));
            throw $e;
        }
    }

}
