<?php
namespace OnitsukaTiger\CustomStoreLocator\Block;

use Magento\Framework\View\Element\Template;
use OnitsukaTiger\CustomStoreLocator\Model\ResourceModel\Grid\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class StoreList extends Template
{
    protected CollectionFactory $collectionFactory;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        Template\Context $context,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager      = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get active stores for current store view
     *
     * @return \OnitsukaTiger\CustomStoreLocator\Model\ResourceModel\Grid\Collection
     */
    public function getStoreCollection()
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        return $this->collectionFactory->create()
            ->addFieldToFilter('stores', ['finset' => $storeId])
            ->addFieldToFilter('store_status', 1)
            ->setOrder('position', 'ASC');
    }

    /**
     * Get media URL for store image
     *
     * @param string $filePath Relative path from media folder
     * @return string
     */
    public function getStoreImageUrl(string $filePath): string
    {
        if (!$filePath) {
            return '';
        }
        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . 'gallery/image/'. ltrim($filePath, '/');
    }

    /**
     * Get media URL for mobile store image
     *
     * @param string $filePath Relative path from media folder
     * @return string
     */
    public function getMobileStoreImageUrl(string $filePath): string
    {
        if (!$filePath) {
            return '';
        }
        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . 'mobile_gallery/image/'. ltrim($filePath, '/');
    }
}
