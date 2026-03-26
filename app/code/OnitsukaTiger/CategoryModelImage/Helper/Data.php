<?php

namespace OnitsukaTiger\CategoryModelImage\Helper;

use Magento\Framework\App\ObjectManager;
use OnitsukaTiger\CategoryModelImage\Model\ResourceModel\CategoryModelImage\Gallery\Image\Collection;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var
     */
    protected $imageGalleryCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Media\Config
     */
    private $imageMediaConfig;

    /**
     * @var \Magento\Framework\View\Design\ThemeInterface
     */
    protected $_design ;

    /**
     * Data constructor.
     * @param Collection $imageGalleryCollection
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        Collection $imageGalleryCollection,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\DesignInterface $theme,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Media\Config $imageMediaConfig = null,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->imageGalleryCollection = $imageGalleryCollection;
        $this->_storeManager = $storeManager;
        $this->_coreRegistry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->_design = $theme->getDesignTheme();
        $this->imageMediaConfig = $imageMediaConfig ?: ObjectManager::getInstance()
            ->get(\OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Media\Config::class);
        parent::__construct($context);
    }

    /**
     * @param $totalCount
     * @return bool|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategoryModelImageImage($totalCount){
        (int)$totalCount++;
        if(strpos($this->_design->getThemePath(),'onitsuka')){
            if($this->getCategoryId()){
                $images = $this->imageGalleryCollection->getByCategoryIdStore($this->getCategoryId()->getEntityId(),$this->getStoreId(),true,$totalCount) ?: null;
                if(!count($images)) {
                    $images = $this->imageGalleryCollection->getByCategoryId($this->getCategoryId()->getEntityId(),true,$totalCount) ?: null;
                }
                $this->_coreRegistry->unregister('current_images');
                $this->_coreRegistry->register('current_images', $images);
                return $images;
            }
        }
        return false;
    }

    public function getCategoryId(){
        return $this->_coreRegistry->registry('current_category');
    }

    public function getImagesCollections(){
        return $this->_coreRegistry->registry('current_images');
    }
    public function getImage() {
        $result = [];
        if($images = $this->getImagesCollections()){
            foreach ($images as $image) {
                $result[$image->getPosition()] = [
                    'link' => $image->getLink(),
                    'alt_text' => $image->getAltText(),
                    'value' => $this->imageMediaConfig->getBaseMediaUrl(). $image->getValue()
                ];
            }
        }
        return $result;
    }

    /**
     * Get store identifier
     *
     * @return  int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @param $storeId
     */
    public function getLimitConfigution($storeId){
        return $storeId;
    }
}
