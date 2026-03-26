<?php
/**
 * Configurable
 */

namespace OnitsukaTiger\PreOrders\Preference\Model\Product\Type;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Collection\SalableProcessor;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\File\UploaderFactory;

class Configurable extends \Magento\ConfigurableProduct\Model\Product\Type\Configurable
{
    public function __construct(
        \Magento\Catalog\Model\Product\Option                                                                  $catalogProductOption,
        \Magento\Eav\Model\Config                                                                              $eavConfig,
        \Magento\Catalog\Model\Product\Type                                                                    $catalogProductType,
        \Magento\Framework\Event\ManagerInterface                                                              $eventManager,
        \Magento\MediaStorage\Helper\File\Storage\Database                                                     $fileStorageDb,
        \Magento\Framework\Filesystem                                                                          $filesystem,
        \Magento\Framework\Registry                                                                            $coreRegistry,
        \Psr\Log\LoggerInterface                                                                               $logger,
        ProductRepositoryInterface                                                                             $productRepository,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\ConfigurableFactory                      $typeConfigurableFactory,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory                                              $eavAttributeFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable\AttributeFactory                          $configurableAttributeFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory   $productCollectionFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory $attributeCollectionFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable                             $catalogProductTypeConfigurable,
        \Magento\Framework\App\Config\ScopeConfigInterface                                                     $scopeConfig,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface                                       $extensionAttributesJoinProcessor,
        \Magento\Framework\Cache\FrontendInterface                                                             $cache = null,
        \Magento\Customer\Model\Session                                                                        $customerSession = null,
        \Magento\Framework\Serialize\Serializer\Json                                                           $serializer = null,
        ProductInterfaceFactory                                                                                $productFactory = null,
        SalableProcessor                                                                                       $salableProcessor = null,
        ProductAttributeRepositoryInterface                                                                    $productAttributeRepository = null,
        SearchCriteriaBuilder                                                                                  $searchCriteriaBuilder = null,
        UploaderFactory                                                                                        $uploaderFactory = null
    ) {
        parent::__construct($catalogProductOption, $eavConfig, $catalogProductType, $eventManager, $fileStorageDb, $filesystem, $coreRegistry, $logger, $productRepository, $typeConfigurableFactory, $eavAttributeFactory, $configurableAttributeFactory, $productCollectionFactory, $attributeCollectionFactory, $catalogProductTypeConfigurable, $scopeConfig, $extensionAttributesJoinProcessor, $cache, $customerSession, $serializer, $productFactory, $salableProcessor, $productAttributeRepository, $searchCriteriaBuilder, $uploaderFactory);
    }

    /**
     * @param $attributesInfo
     * @param $product
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductByAttributes($attributesInfo, $product)
    {
        if (is_array($attributesInfo) && !empty($attributesInfo)) {
            $productCollection = $this->getUsedProductCollection($product)->addAttributeToSelect('name');
            foreach ($attributesInfo as $attributeId => $attributeValue) {
                $productCollection->addAttributeToFilter($attributeId, $attributeValue);
            }
            /** @var \Magento\Catalog\Model\Product $productObject */
            // $productObject = $productCollection->getFirstItem();
            // $productLinkFieldId = $productObject->getId();
            
            $productLinkProductId = $productLinkFieldId = '';
            if(!empty($productCollection->getData()) && isset($productCollection->getData()[0]) && isset($productCollection->getData()[0]['entity_id'])){
                $productLinkProductId = $productCollection->getData()[0]['entity_id'];
            }
            if($productLinkProductId){
                $productLinkFieldId = $productLinkProductId;
            }
            if ($productLinkFieldId) {
                return $this->productRepository->getById($productLinkFieldId);
            }

            foreach ($productCollection as $productObject) {
                $checkRes = true;
                foreach ($attributesInfo as $attributeId => $attributeValue) {
                    $code = $this->getAttributeById($attributeId, $product)->getAttributeCode();
                    if ($productObject->getData($code) != $attributeValue) {
                        $checkRes = false;
                    }
                }
                if ($checkRes) {
                    return $productObject;
                }
            }
        }
        return null;
    }
}
