<?php
declare(strict_types=1);

namespace OnitsukaTiger\Relation\Plugin\Block\Product\Renderer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\App\Emulation;
use Magento\Swatches\Block\Product\Renderer\Configurable as ConfigurableBlock;
use OnitsukaTiger\Relation\Helper\Data as HelperRelation;

class Configurable
{
    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;
    /**
     * @var HelperRelation
     */
    private HelperRelation $helperRelation;

    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * @var Magento\Framework\App\RequestInterface
     */
    private $requestData;
    /**
     * @var Emulation
     */
    protected $appEmulation;

    /**
     * @param Registry $coreRegistry
     * @param HelperRelation $helperRelation
     * @param Emulation $appEmulation
     * @param AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\App\RequestInterface $requestData
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        Registry                                                               $coreRegistry,
        HelperRelation                                                         $helperRelation,
        Emulation                                                              $appEmulation,
        AttributeRepositoryInterface                                           $attributeRepository,
        \Magento\Framework\App\RequestInterface                                $requestData,
        private \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->helperRelation = $helperRelation;
        $this->requestData = $requestData;
        $this->appEmulation = $appEmulation;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Add swatch data in json
     *
     * @param ConfigurableBlock $subject
     * @param string $result
     * @return array|false|mixed|string|string[]|null
     * @throws LocalizedException
     */
    public function afterGetJsonSwatchConfig(ConfigurableBlock $subject, string $result): mixed
    {
        if ($this->requestData->getFullActionName() == 'cms_index_index') {
            return $result;
        }

        if (!$this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ENABLE)) {
            return $result;
        }
        $product = $subject->getProduct();
        $currentProduct = $this->productCollectionFactory->create()->addStoreFilter($product->getStoreId())
            ->addAttributeToSelect('style_code')
            ->addAttributeToFilter('entity_id', $product->getId())
            ->getFirstItem();
        $jsonArr = $this->getAssociateProduct($currentProduct->getStyleCode(), $currentProduct->getSku(), $currentProduct->getStoreId());

        $currentProduct = $subject->getProduct();

        if (!str_contains($subject->getRequest()->getPathInfo(), 'catalog/product/view/') && $this->requestData->getFullActionName() != 'quickview_catalog_product_view') {
            $jsonRelation = $currentProduct->getData('json_relation');
            if ($jsonRelation) {
                $result = [];
                foreach ($jsonArr as $json) {
                    $attributeRegistry = $json['swatches_attribute'] . $json['swatches_attribute_id'];
                    $registryId = 'product_swatches' . $attributeRegistry;
                    $registryValue = 'product_swatches_value' . $attributeRegistry;
                    if ($this->coreRegistry->registry($registryId)) {
                        if ($this->coreRegistry->registry($registryValue)) {
                            $result[] = $json;
                        }
                    } else {
                        $this->coreRegistry->unregister($registryId);
                        $this->coreRegistry->unregister($registryValue);
                        $this->coreRegistry->register($registryId, true);
                        $this->coreRegistry->register($registryValue, true);
                        $result[] = $json;
                    }
                }
                $result['urlLoadProduct'] = $subject->getUrl('relation/index');
                return json_encode($result);
            }
            return '';
        }

        $result = json_decode($result, true);
        $jsonRelation = $currentProduct->getData('json_relation');
        $jsonRelationArr = [];
        if ($jsonRelation) {
            $jsonRelationArr = json_decode($jsonRelation, true);
        }

        foreach ($result as $attributeId => $attributes) {
            if (!is_array($attributes)) {
                continue;
            }
            foreach ($attributes as $optionId => $option) {
                if (!is_array($option)) {
                    continue;
                }
                $result = $this->getProductImage(
                    $jsonRelationArr,
                    $result,
                    $option,
                    $optionId,
                    $attributeId
                );
            }
        }
        return json_encode($result);
    }

    /**
     * @param $styleCode
     * @param $sku
     * @param $storeId
     * @return array
     */
    public function getAssociateProduct($styleCode, $sku, $storeId)
    {
        $collection = $this->productCollectionFactory->create()
            ->addStoreFilter($storeId)
            ->addAttributeToFilter('style_code', $styleCode)
            ->addAttributeToFilter('type_id', 'configurable')
            ->addAttributeToSelect('json_relation')
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', Visibility::VISIBILITY_BOTH);
        if ($collection) {
            $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
            $colorArr = [];
            $jsonArr = [];
            foreach ($collection as $product) {
                try {
                    if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                        $_children = $product->getTypeInstance()->getUsedProductCollection($product)->addStoreFilter($storeId)->addAttributeToSelect('color');
                        $productSku = $product->getSku();
                        $jsonRelation = $product->getData('json_relation');
                        $jsonRelation = json_decode($jsonRelation, true);
                        if ($_children) {
                            foreach ($_children as $child) {
                                if ($child->getStatus() == 2) {
                                    continue;
                                }
                                $colorOptionId = $child->getColorCode();
                                if (!in_array($colorOptionId, $colorArr)) {
                                    foreach ($jsonRelation as $index => $json) {
                                        if ($productSku == $json['product_sku']) {
                                            break;
                                        }
                                    }
                                    $jsonArr[$index] = $json;
                                }
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            $this->appEmulation->stopEnvironmentEmulation();
        }
        return $jsonArr;
    }

    /**
     * Get Product Image
     *
     * @param array $jsonRelation
     * @param array $result
     * @param array $option
     * @param int|string $optionId
     * @param int|string $attributeId
     * @return array
     */
    private function getProductImage(
        array      $jsonRelation,
        array      $result,
        array      $option,
        int|string $optionId,
        int|string $attributeId
    ): array {
        if (!$option['value'] || str_contains($option['value'], 'product/placeholder') && !empty($jsonRelation)) {
            foreach ($jsonRelation as $json) {
                if ($optionId == $json['swatches_attribute_id']) {
                    $result[$attributeId][$optionId]['value'] = $json['swatches_image'];
                    $result[$attributeId][$optionId]['thumb'] = $json['swatches_image'];
                }

            }
        }
        return $result;
    }
}