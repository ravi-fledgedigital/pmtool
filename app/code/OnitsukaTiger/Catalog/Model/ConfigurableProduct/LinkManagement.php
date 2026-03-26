<?php
namespace OnitsukaTiger\Catalog\Model\ConfigurableProduct;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\AddOptionToAttribute;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Configurable product link management.
 * @package OnitsukaTiger\Catalog\Model\ConfigurableProduct
 */
class LinkManagement implements \Magento\ConfigurableProduct\Api\LinkManagementInterface
{
    /**
     * Default options config path
     */
    const DEFAULT_OPTIONS_PATH = 'cli_command/register_associated_product/default_attribute_code_options';

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterfaceFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $configurableType;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\ConfigurableProduct\Helper\Product\Options\Factory;
     */
    protected $optionsFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var AddOptionToAttribute
     */
    protected $addOption;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var ModuleDataSetupInterface
     */
    protected $setup;

    /**
     * Constructor
     *
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $eavConfig
     * @param AddOptionToAttribute $addOption
     * @param ModuleDataSetupInterface $setup
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        ScopeConfigInterface $scopeConfig,
        Config $eavConfig,
        AddOptionToAttribute $addOption,
        ModuleDataSetupInterface $setup,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory = null
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->configurableType = $configurableType;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->attributeFactory = $attributeFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory::class);
        $this->scopeConfig = $scopeConfig;
        $this->eavConfig = $eavConfig;
        $this->addOption = $addOption;
        $this->setup = $setup;
    }

    /**
     * @inheritdoc
     */
    public function getChildren($sku)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->get($sku);
        if ($product->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return [];
        }

        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productTypeInstance */
        $productTypeInstance = $product->getTypeInstance();
        $productTypeInstance->setStoreFilter($product->getStoreId(), $product);

        $childrenList = [];
        /** @var \Magento\Catalog\Model\Product $child */
        foreach ($productTypeInstance->getUsedProducts($product) as $child) {
            $attributes = [];
            foreach ($child->getAttributes() as $attribute) {
                $attrCode = $attribute->getAttributeCode();
                $value = $child->getDataUsingMethod($attrCode) ?: $child->getData($attrCode);
                if (null !== $value) {
                    $attributes[$attrCode] = $value;
                }
            }
            $attributes['store_id'] = $child->getStoreId();
            /** @var ProductInterface $productDataObject */
            $productDataObject = $this->productFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $productDataObject,
                $attributes,
                ProductInterface::class
            );
            $childrenList[] = $productDataObject;
        }
        return $childrenList;
    }

    /**
     * @param ProductInterface $product
     * @return OptionInterface[]
     * @throws LocalizedException
     * @throws StateException
     */
    public function checkAttrValue(ProductInterface $product): array
    {
        $attributeData = [];
        $position = 0;
        $defaultOptions = $this->getDefaultOptionsConfig();
        foreach ($defaultOptions as $code) {
            $attribute = $this->getAttributeByCode($code);
            $attributeCode = $attribute->getAttributeCode();

            if (!$product->getData($attributeCode)) {
                throw new StateException(__('The child product doesn\'t have the "%1" attribute value. Verify the value and try again.', $attributeCode));
            }
            $attributeData[$attribute->getAttributeId()] = [
                'position' => $position
            ];
            $position++;
        }

        $configurableOptionData = $this->getConfigurableAttributesData($attributeData);
        $optionFactory = $this->getOptionsFactory();
        return $optionFactory->create($configurableOptionData);
    }

    /**
     * @param string $sku
     * @param string $childSku
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function addChild($sku, $childSku): bool
    {
        $product = $this->productRepository->get($sku, true);
        $child = $this->productRepository->get($childSku);

        $childrenIds = array_values($this->configurableType->getChildrenIds($product->getId())[0]);
        if (in_array($child->getId(), $childrenIds)) {
            return true;
        }

        $options = $this->checkAttrValue($child);
        $childrenIds[] = $child->getId();
        $product->getExtensionAttributes()->setConfigurableProductOptions($options);
        $product->getExtensionAttributes()->setConfigurableProductLinks($childrenIds);
        $this->productRepository->save($product);
        return true;
    }

    /**
     * @param $product
     * @param $childProd
     * @return array
     * @throws LocalizedException
     * @throws StateException
     */
    public function addMoreChild($product, $childProd): array
    {
        $options = $this->checkAttrValue($childProd);
        return [$options, $childProd->getId()];
    }

    /**
     * @inheritdoc
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws CouldNotSaveException
     */
    public function removeChild($sku, $childSku)
    {
        $product = $this->productRepository->get($sku);

        if ($product->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            throw new InputException(
                __('The product with the "%1" SKU isn\'t a configurable product.', $sku)
            );
        }

        $options = $product->getTypeInstance()->getUsedProducts($product);
        $ids = [];
        foreach ($options as $option) {
            if ($option->getSku() == $childSku) {
                continue;
            }
            $ids[] = $option->getId();
        }
        if (count($options) == count($ids)) {
            throw new NoSuchEntityException(
                __("The option that was requested doesn't exist. Verify the entity and try again.")
            );
        }
        $product->getExtensionAttributes()->setConfigurableProductLinks($ids);
        $this->productRepository->save($product);
        return true;
    }

    /**
     * @param $sku
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws CouldNotSaveException
     */
    public function removeAllChild($sku)
    {
        $product = $this->productRepository->get($sku);

        if ($product->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            throw new InputException(
                __('The product with the "%1" SKU isn\'t a configurable product.', $sku)
            );
        }

        $product->getExtensionAttributes()->setConfigurableProductLinks([]);
        $this->productRepository->save($product);
        return true;
    }

    /**
     * Get Options Factory
     *
     * @return \Magento\ConfigurableProduct\Helper\Product\Options\Factory
     *
     * @deprecated 100.2.0
     */
    public function getOptionsFactory()
    {
        if (!$this->optionsFactory) {
            $this->optionsFactory = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\ConfigurableProduct\Helper\Product\Options\Factory::class);
        }
        return $this->optionsFactory;
    }

    /**
     * Get Configurable Attribute Data
     *
     * @param int[] $attributeData
     * @return array
     */
    protected function getConfigurableAttributesData($attributeData)
    {
        $configurableAttributesData = [];
        $attributeValues = [];
        $attributes = $this->attributeFactory->create()
            ->getCollection()
            ->addFieldToFilter('attribute_id', array_keys($attributeData))
            ->getItems();
        foreach ($attributes as $attribute) {
            foreach ($attribute->getOptions() as $option) {
                if ($option->getValue()) {
                    $attributeValues[] = [
                        'label' => $option->getLabel(),
                        'attribute_id' => $attribute->getId(),
                        'value_index' => $option->getValue(),
                    ];
                }
            }
            $configurableAttributesData[] =
                [
                    'attribute_id' => $attribute->getId(),
                    'code' => $attribute->getAttributeCode(),
                    'label' => $attribute->getStoreLabel(),
                    'position' => $attributeData[$attribute->getId()]['position'],
                    'values' => $attributeValues,
                ];
        }

        return $configurableAttributesData;
    }

    /**
     * @param $code
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     * @throws LocalizedException
     */
    protected function getAttributeByCode($code)
    {
        return $this->attributeFactory->create()->loadByCode(Product::ENTITY, $code);
    }

    /**
     * Get default options array from config
     *
     * @return array
     */
    public function getDefaultOptionsConfig()
    {
        $options = $this->scopeConfig->getValue(self::DEFAULT_OPTIONS_PATH);
        return explode(',', $options);
    }

    /**
     * @param $product
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function setColorCodeAttributeToProductChild($product)
    {
        $productChild = $this->productRepository->getById($product->getId());
        if (!$productChild->getData(\OnitsukaTiger\Catalog\Model\Product::COLOR_CODE)) {
            $colorCode = explode('.', $productChild->getSku())[1];
            $attribute = $this->eavConfig->getAttribute('catalog_product', \OnitsukaTiger\Catalog\Model\Product::COLOR_CODE);
            $options = $attribute->getSource()->getAllOptions();
            $optionColorCode = array_column($options, 'label');

            if (in_array($colorCode, $optionColorCode)) {
                $optionId = $attribute->getSource()->getOptionId($colorCode);
            } else {
                $option = [
                    'attribute_id' => $attribute->getAttributeId(),
                    'values' => [$colorCode]
                ];
                $this->addOption->execute($option);
                $optionId = $this->getOptionId($colorCode, $attribute->getAttributeId());
            }

            $productChild->setData(\OnitsukaTiger\Catalog\Model\Product::COLOR_CODE, $optionId);
            $this->productRepository->save($productChild);
        }
    }

    /**
     * @param $value
     * @param $attributeId
     * @return string
     */
    public function getOptionId($value, $attributeId): string
    {
        $connection = $this->setup->getConnection();
        $select = $connection->select()
            ->from(
                ['attribute_option' => $this->setup->getTable('eav_attribute_option')],
                ['option_id']
            )->joinInner(
                ['attribute_value' => $this->setup->getTable('eav_attribute_option_value')],
                'attribute_option.option_id = attribute_value.option_id',
                []
            )
            ->where('attribute_value.value = ?', $value)
            ->where('attribute_option.attribute_id = ?', $attributeId);

        return $connection->fetchOne($select);
    }
}
