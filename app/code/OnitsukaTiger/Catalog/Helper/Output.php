<?php

declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Helper;


/**
 * Class Output
 * @package OnitsukaTiger\Catalog\Helper
 */
class Output extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Output constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param string $url
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageUrl(string $url) {
        $url = explode('/',$url);
        $mediaUrl = $this->_storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl.'catalog/category/'.end($url);
    }
}
