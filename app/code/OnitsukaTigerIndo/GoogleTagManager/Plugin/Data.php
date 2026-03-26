<?php

namespace OnitsukaTigerIndo\GoogleTagManager\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use OnitsukaTigerIndo\GoogleTagManager\Helper\Data as HelperData;
use WeltPixel\GA4\Helper\Data as WeltpixelData;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use WeltPixel\GA4\Model\Config\Source\ParentVsChild;

class Data
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var Configurable
     */
    private Configurable $configurable;
    /**
     * @var Grouped
     */
    private Grouped $grouped;

    /**
     * Data constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param HelperData $helperData
     * @param Registry $registry
     * @param Configurable $configurable
     * @param Grouped $grouped
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        HelperData $helperData,
        Registry $registry,
        Configurable $configurable,
        Grouped $grouped,
    )
    {
        $this->productRepository = $productRepository;
        $this->helperData = $helperData;
        $this->registry = $registry;
        $this->configurable = $configurable;
        $this->grouped = $grouped;
    }
    /**
     * Returns the product id or sku based on the backend settings
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function afterGetGtmProductId( WeltpixelData $subject, $result)
    {
        if ( !$this->helperData->validStoreCode() ){
            return $result;
        }
        //apply for sku option
        $idOption = $this->helperData->getGtmOptions('general','id_selection');
        if ( $idOption == 'id' ){
            return $result;
        }

        $product = $this->registry->registry('current_product');
        $displayOption = $this->helperData->getGtmOptions('general','parent_vs_child');

        if ( ($displayOption == ParentVsChild::CHILD)
            && ($product->getTypeId() == Configurable::TYPE_CODE)) {
            $parentIds = $this->configurable->getParentIdsByChild( $product->getEntityId() );
            if (!empty($parentIds)) {
                $productConfig = $this->productRepository->getById($parentIds[0]);
                $result =  $productConfig->getSku();
            }

        }
        return $this->helperData->principalSku($result);
    }

    /**
     * @param WeltpixelData $subject
     * @param $result
     * @return mixed|string
     */
    public function afterGetGtmBrand( WeltpixelData $subject, $result){

        if ( !$this->helperData->validStoreCode() ){
            return $result;
        }
        return HelperData::GTM_BRAND;
    }

}
