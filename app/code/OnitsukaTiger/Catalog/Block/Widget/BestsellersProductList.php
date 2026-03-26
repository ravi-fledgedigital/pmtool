<?php

namespace OnitsukaTiger\Catalog\Block\Widget;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\CatalogWidget\Model\Rule;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Helper\Conditions;
use OnitsukaTiger\Catalog\Model\ResourceModel\BestsellersProduct\CollectionFactory as BestSellerCollection;

class BestsellersProductList extends ProductsList
{
    public const DEFAULT_RANGE_DAYS = "30";

    /**
     * @var BestSellerCollection
     */
    protected BestSellerCollection $bestsellersCollection;

    /**
     * @var Stock
     */
    protected Stock $stock;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resourceConnection;

    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        HttpContext $httpContext,
        SqlBuilder $sqlBuilder,
        Rule $rule,
        Conditions $conditionsHelper,
        BestSellerCollection $bestsellersCollection,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        Stock $stock,
        array $data = [],
        Json $json = null,
        LayoutFactory $layoutFactory = null,
        EncoderInterface $urlEncoder = null,
        CategoryRepositoryInterface $categoryRepository = null
    ) {
        parent::__construct(
            $context,
            $productCollectionFactory,
            $catalogProductVisibility,
            $httpContext,
            $sqlBuilder,
            $rule,
            $conditionsHelper,
            $data,
            $json,
            $layoutFactory,
            $urlEncoder,
            $categoryRepository
        );
        $this->storeManager = $storeManager;
        $this->bestsellersCollection = $bestsellersCollection;
        $this->stockHelper = $stock;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function createCollection()
    {
        $storeId = $this->getData('store_id');
        $sortOrder = $this->getData('sort_order');
        $connection = $this->resourceConnection->getConnection();
        $select = $this->getBestsellerProduct($storeId, $this->getBestPeriod(), $sortOrder);
        $bestsellersCollection = $connection->fetchAll($select);
        $skuProducts = [];
        foreach ($bestsellersCollection as $product) {
            if (count($skuProducts) == $this->getProductsCount()) {
                break;
            }
            if ($product['material_code']
                && !in_array($product['material_code'], $skuProducts)
                && $this->checkStockProduct($product['material_code'], $storeId)) {
                $skuProducts[] = $product['material_code'];
            }
        }

        $collectionProduct = $this->productCollectionFactory->create()
            ->addAttributeToSelect("*")
            ->addFieldToFilter('type_id', Configurable::TYPE_CODE)
            ->addFieldToFilter('sku', ['in' => $skuProducts]);

        if (count($skuProducts)) {
            $sku = implode(',', $skuProducts);
            $skuStr = "'" . str_replace(",", "','", $sku) . "'";
            $collectionProduct->getSelect()->order("FIELD(e.sku, $skuStr)");
        }
        return $collectionProduct;
    }

    /**
     * @param $storeId
     * @param $range
     * @param $sortOrder
     * @return string
     */
    public function getBestsellerProduct($storeId, $range, $sortOrder)
    {
        $time = '-' . $range . ' days';
        $fromDate = date('Y-m-d', strtotime($time));
        $toDate = date('Y-m-d');
        return "(SELECT DATE_FORMAT(period, '%Y') AS `period`, SUM(qty_ordered) AS `qty_ordered`, `sales_bestsellers_aggregated_yearly`.`product_id`,null AS `material_code`, MAX(product_name) AS `product_name`, MAX(product_price) AS `product_price` FROM `sales_bestsellers_aggregated_yearly` WHERE (rating_pos <= 1000) AND (period >= '$fromDate') AND (period <= '$toDate') AND (store_id IN($storeId)) AND (store_id IN($storeId)) AND (1<>1) AND (period >= '$fromDate') AND (period <= '$toDate') GROUP BY `period`, `product_id`) UNION ALL (SELECT DATE_FORMAT(period, '%Y') AS `period`, SUM(qty_ordered) AS `qty_ordered`, `bestsellers_product_list`.`product_id`,`bestsellers_product_list`.`material_code`, MAX(product_name) AS `product_name`, MAX(product_price) AS `product_price` FROM `bestsellers_product_list` WHERE (period >= '$fromDate') AND (period <= '$toDate') AND (store_id IN($storeId)) GROUP BY `product_id` ORDER BY `qty_ordered` $sortOrder LIMIT 1000) ORDER BY `qty_ordered` $sortOrder;";
    }

    /**
     * @param $code
     * @param $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function checkStockProduct($code, $storeId): bool
    {
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $productConfigurable = $this->getProductVisibility($code, Configurable::TYPE_CODE, $websiteId);
        $productChildren = $this->getProductVisibility($code, Type::TYPE_SIMPLE, $websiteId);
        if ($productChildren && $productConfigurable) {
            return true;
        }
        return false;
    }

    /**
     * @param $materialCode
     * @param $typeProduct
     * @param $websiteId
     * @return DataObject[]
     */
    public function getProductVisibility($materialCode, $typeProduct, $websiteId)
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToFilter('type_id', $typeProduct)
            ->addWebsiteFilter($websiteId)
            ->addFieldToFilter('status', ['eq' => Status::STATUS_ENABLED])
            ->addAttributeToFilter('material_code', $materialCode);
        if ($typeProduct == Type::TYPE_SIMPLE) {
            $this->stockHelper->addInStockFilterToCollection($collection);
        } else {
            $collection->addFieldToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);
        }
        return $collection->getItems();
    }

    /**
     * @return array|mixed|null
     */
    public function getBestPeriod()
    {
        if (null === $this->getData('best_period')) {
            $this->setData('best_period', self::DEFAULT_RANGE_DAYS);
        }

        return $this->getData('best_period');
    }
}
