<?php
declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Helper;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ConfigurablePrice
 * @package OnitsukaTiger\Catalog\Helper
 */
class ConfigurablePrice extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    protected $stockByWebsiteId;

    /**
     * @var IsProductSalableInterface
     */
    protected $isProductSalable;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    protected $isProductSalableForRequestedQty;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ConfigurablePrice constructor.
     * @param CollectionFactory $productCollectionFactory
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param IsProductSalableInterface $isProductSalable
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        IsProductSalableInterface $isProductSalable,
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        StoreManagerInterface $storeManager,
        private \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct,
        Context $context
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->isProductSalable = $isProductSalable;
        $this->stockByWebsiteId = $stockByWebsiteId;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getChildrenProductCollection(Product $product)
    {
        /*$productCollection = $this->productCollectionFactory->create()->setFlag(
            'product_children',
            true
        )->setProductFilter(
            $product
        );*/

        $productCollection = $this->configurableProduct->getUsedProductCollection($product);
        $productCollection->addAttributeToSelect(['price', 'special_price', 'material_code'])
            ->addAttributeToFilter('material_code', $product->getMaterialCode())
            ->addAttributeToFilter('price', ['gt' => 0])
            ->addStoreFilter($product->getStoreId());

        return $productCollection;
    }

    /**
     * @param Product $product
     * @param null $websiteId
     * @return array
     * @throws LocalizedException
     */
    public function getMinimalPrice(Product $product, $websiteId = null)
    {
        $websiteId = $websiteId ?? $this->getCurrentWebsiteId();
        $prices = $specialPrices = $productChildrenId = null;
        if (count($this->getChildrenProductCollection($product))) {
            $pricesChildren = $specialPricesChildren = $productChildren = [];
            foreach ($this->getChildrenProductCollection($product) as $childProduct) {
                if (in_array($websiteId, $product->getWebsiteIds())) {
                    $pricesChildren[$childProduct->getId()] = $childProduct->getPrice();
                    $productChildren[$childProduct->getId()] = $childProduct;
                    if ($childProduct->getSpecialPrice()) {
                        $specialPricesChildren[$childProduct->getId()] = $childProduct->getSpecialPrice();
                    }
                }
            }
            if (!empty($pricesChildren)) {
                $minProductPrice = empty($specialPricesChildren) ? min($pricesChildren) : min([min($pricesChildren), min($specialPricesChildren)]);
                $minProductId = array_search($minProductPrice, $pricesChildren);
                if (!empty($specialPricesChildren)) {
                    $minProductId = array_search($minProductPrice, $specialPricesChildren) ? array_search($minProductPrice, $specialPricesChildren) : $minProductId;
                }

                $prices = min($pricesChildren);
                $specialPrices = empty($specialPricesChildren) ? null : min($specialPricesChildren);
                $productChildrenId = $productChildren[$minProductId];
            }
        }
        return [
            'price' => $prices,
            'special_price' => $specialPrices,
            'product_children' => $productChildrenId
        ];
    }

    /**
     * @param int $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteIdByStoreId(int $storeId)
    {
        return (int)$this->storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCurrentWebsiteId()
    {
        return (int)$this->storeManager->getStore()->getWebsiteId();
    }
}
