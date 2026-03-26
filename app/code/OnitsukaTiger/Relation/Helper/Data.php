<?php

declare(strict_types=1);

namespace OnitsukaTiger\Relation\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ModelCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\Collection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data
{
    public const XML_PATH_RELATION_ENABLE = 'relation/general/enable';
    public const XML_PATH_RELATION_ATTRIBUTES = 'relation/general/related';
    public const IMAGE_PARAMS_SWATCHES_JSON = '?qlt=80&wid=105&hei=78&bgc=255,255,255&resMode=bisharp';

    public const IMAGE_PARAMS_SWATCHES_JSON_IMAGE = '"type":"3","value"';

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $attributeCollectionFactory;

    /**
     * @var JoinProcessorInterface
     */
    private JoinProcessorInterface $extensionAttributesJoinProcessor;

    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @var StockHelper
     */
    private StockHelper $stockHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param CollectionFactory $attributeCollectionFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StockHelper $stockHelper
     * @param Context $context
     */
    public function __construct(
        CollectionFactory        $attributeCollectionFactory,
        JoinProcessorInterface   $extensionAttributesJoinProcessor,
        ProductCollectionFactory $productCollectionFactory,
        StockHelper              $stockHelper,
        Context                  $context
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockHelper = $stockHelper;
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * Get All Product Configurable with relation attribute
     *
     * @param string $attributeValue
     * @return ModelCollection
     */
    public function getProductsConfigurable(string $attributeValue): ModelCollection
    {
        return $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', "configurable")
            ->addAttributeToFilter($this->getConfig(self::XML_PATH_RELATION_ATTRIBUTES), $attributeValue);
    }

    /**
     * Get Product Relation Data
     *
     * @param Product $product
     * @return mixed|null
     */
    public function getAttributeRelation(Product $product)
    {
        return $product->getData($this->getConfig(self::XML_PATH_RELATION_ATTRIBUTES));
    }

    /**
     * Get All Product Children
     *
     * @param array $productIds
     * @return mixed
     */
    public function getAllChildren(array $productIds): mixed
    {
        if (isset($productIds[0]) && !empty($productIds[0])) {
            return $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter(
                'entity_id',
                [
                    'in' => $productIds
                ]
            );
        } else {
            return [];
        }
    }

    /**
     * Check Product Stock
     *
     * @param string $relation
     * @param string $color
     * @return ModelCollection
     */
    public function getProductStock(string $relation, string $color)
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('type_id')
            ->addAttributeToSelect('color')
            ->addAttributeToFilter('type_id', "simple")
            ->addAttributeToFilter('color', $color)
            ->addAttributeToFilter(
                $this->getConfig(self::XML_PATH_RELATION_ATTRIBUTES),
                $relation
            );
        $this->stockHelper->addInStockFilterToCollection($collection);
        return $collection;
    }

    /**
     * Get All Children With Stock
     *
     * @param array $productIds
     * @return mixed
     */
    public function getChildrenProductByIds(array $productIds): mixed
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', "simple")
            ->addFieldToFilter(
                'entity_id',
                [
                    'in' => $productIds
                ]
            );
        // echo '<pre>';
        // print_r($collection->getData());
        // die();
        //$this->stockHelper->addInStockFilterToCollection($collection);
        return $collection;
    }

    /**
     * Retrieve configurable attributes data
     *
     * @param Product $product
     * @return Collection|array
     */
    public function getConfigurableAttributes(Product $product): Collection|array
    {
        // for new product do not load configurable attributes
        if (!$product->getId()) {
            return [];
        }
        $configurableAttributes = $this->getConfigurableAttributeCollection($product);
        $this->extensionAttributesJoinProcessor->process($configurableAttributes);
        $configurableAttributes->orderByPosition()->load();
        return $configurableAttributes;
    }

    /**
     * Get Store Configuration
     *
     * @param string $path
     * @return mixed
     */
    public function getConfig(string $path): mixed
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Retrieve configurable attribute collection
     *
     * @param Product $product
     * @return Collection
     */
    public function getConfigurableAttributeCollection(Product $product): Collection
    {
        return $this->attributeCollectionFactory->create()->setProductFilter($product);
    }
}
