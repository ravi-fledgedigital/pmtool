<?php


namespace OnitsukaTiger\NetSuite\Plugin;

class ProductPlugin
{
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;
    /**
     * @var \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct
     */
    protected $enableProduct;
    /**
     * @var \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface
     */
    protected $message;
    /**
     * @var array
     */
    protected $attributeMap;

    /**
     * ProductPlugin constructor.
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct $enableProduct
     * @param \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface $message
     */
    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct $enableProduct,
        \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface $message
    )
    {
        $this->publisher = $publisher;
        $this->enableProduct = $enableProduct;
        $this->message = $message;

        $this->attributeMap = $enableProduct->getAttributeMap();
    }

    /**
     * @param \Magento\Catalog\Model\Product $subject
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     */
    public function afterSave(
        \Magento\Catalog\Model\Product $subject,
        \Magento\Catalog\Model\Product $product
    ) {
        if($product->dataHasChangedFor('netsuite_enable')) {
            $enables = explode(',', $product->getNetsuiteEnable());
            $this->message->setNetsuiteWebTh(
                in_array($this->attributeMap[\OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::NETSUITE_ID_THAILAND], $enables)
            );
            $this->message->setNetsuiteWebMy(
                in_array($this->attributeMap[\OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::NETSUITE_ID_MALAYSIA], $enables)
            );
            $this->message->setNetsuiteWebSg(
                in_array($this->attributeMap[\OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::NETSUITE_ID_SINGAPORE], $enables)
            );
            $this->message->setNetsuiteWebVn(
                in_array($this->attributeMap[\OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::NETSUITE_ID_VIETNAM], $enables)
            );
            $this->message->setProductId($product->getEntityId());
            $this->message->setRetry(0);
            $this->publisher->publish(
                \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface::TOPIC_NAME,
                $this->message
            );
        }
        return $product;
    }
}
