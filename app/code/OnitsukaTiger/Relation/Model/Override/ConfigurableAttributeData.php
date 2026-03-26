<?php

declare(strict_types=1);

namespace OnitsukaTiger\Relation\Model\Override;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use OnitsukaTiger\Relation\Helper\Data as HelperRelation;
use Magento\Catalog\Model\Product;

class ConfigurableAttributeData extends \Magento\ConfigurableProduct\Model\ConfigurableAttributeData
{

    /**
     * @var HelperRelation
     */
    private HelperRelation $helperRelation;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private \Magento\Framework\App\RequestInterface $request;

    /**
     * Constructor
     *
     * @param Registry $coreRegistry
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\View\Element\Context $context
     * @param HelperRelation $helperRelation
     */
    public function __construct(
        Registry                   $coreRegistry,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\View\Element\Context $context,
        HelperRelation             $helperRelation
    ) {
        $this->request = $context->getRequest();
        $this->coreRegistry = $coreRegistry;
        $this->productRepository = $productRepository;
        $this->helperRelation = $helperRelation;
    }

    /**
     * Get attribute data
     *
     * @param Product $product
     * @param array $options
     * @return array[]
     * @throws NoSuchEntityException
     */
    public function getAttributesData(Product $product, array $options = [])
    {
        $defaultValues = [];
        $attributes = [];
        foreach ($product->getTypeInstance()->getConfigurableAttributes($product) as $attribute) {
            $attributeOptionsData = $this->getAttributeOptionsDataOverride($attribute, $options, $product->getId());
            if ($attributeOptionsData) {
                $productAttribute = $attribute->getProductAttribute();
                $attributeId = $productAttribute->getId();
                $attributes[$attributeId] = [
                    'id' => $attributeId,
                    'code' => $productAttribute->getAttributeCode(),
                    'label' => $productAttribute->getStoreLabel($product->getStoreId()),
                    'options' => $attributeOptionsData,
                    'position' => $attribute->getPosition(),
                ];
                $defaultValues[$attributeId] = $this->getAttributeConfigValue($attributeId, $product);
            }
        }
        return [
            'attributes' => $attributes,
            'defaultValues' => $defaultValues,
        ];
    }

    /**
     * Get All Option from all configurable product
     *
     * @param Attribute $attribute
     * @param array $config
     * @param int $productId
     * @return array
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getAttributeOptionsDataOverride($attribute, $config, $productId): array
    {
        if (!$this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ENABLE)) {
            return parent::getAttributeOptionsData($attribute, $config);
        }

        if (!str_contains($this->request->getPathInfo(), 'catalog/product/view/') && $this->request->getFullActionName() != 'quickview_catalog_product_view') {
            return [];
        }
        $currentProduct = $this->getProduct($productId);
        if ($this->coreRegistry->registry('allow_product_configurable_list')) {
            $productConfigurable = $this->coreRegistry->registry('allow_product_configurable_list');
        } else {
            $productConfigurable = $this->helperRelation->getProductsConfigurable(
                $currentProduct->getData(
                    $this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ATTRIBUTES)
                )
            );
            $this->coreRegistry->unregister('allow_product_configurable_list');
            $this->coreRegistry->register('allow_product_configurable_list', $productConfigurable);
        }

        $currentOptions = $currentOptionsTmp = [];

        foreach ($productConfigurable as $product) {
            foreach ($this->helperRelation->getConfigurableAttributes($product) as $attributeRelation) {
                if ($attribute->getAttributeId() == $attributeRelation->getAttributeId()) {
                    foreach ($attributeRelation->getOptions() as $attributeOptionRelation) {
                        $valueIndex = $attributeOptionRelation['value_index'];
                        if (!array_key_exists($valueIndex, $currentOptionsTmp)) {
                            $currentOptionsTmp[$valueIndex] = $valueIndex;
                            $currentOptions[] = $attributeOptionRelation;
                        }
                    }
                }
            }
        }
        $attributeOptionsData = [];
        foreach ($currentOptions as $attributeOption) {
            $optionId = $attributeOption['value_index'];
            $attributeOptionsData[] = [
                'id' => $optionId,
                'label' => $attributeOption['label'],
                'products' => isset($config[$attribute->getAttributeId()][$optionId])
                    ? $config[$attribute->getAttributeId()][$optionId]
                    : [],
            ];
        }
        return $attributeOptionsData;
    }

    /**
     * Get Product Details
     *
     * @param string $productId
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProduct(string $productId): ProductInterface
    {
        return $this->productRepository->getById($productId, false, null, true);
    }
}
