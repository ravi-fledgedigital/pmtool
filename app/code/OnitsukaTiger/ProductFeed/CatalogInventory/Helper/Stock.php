<?php
namespace OnitsukaTiger\ProductFeed\CatalogInventory\Helper;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

class Stock
{
    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var int
     */
    private $stockId;

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     */
    public function __construct(
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver
    ) {
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
    }

    /**
     * @param Collection $collection
     */
    public function addStockDataToCollection(Collection $collection)
    {

        $resource = $collection->getResource();
        $collection->getSelect()->join(
            ['product' => $resource->getTable('catalog_product_entity')],
            sprintf('product.entity_id = %s.entity_id', Collection::MAIN_TABLE_ALIAS),
            []
        );
        $quantityColumnName = IndexStructure::QUANTITY;
        $collection->getSelect()
            ->join(
                ['stock_status_index' => $this->stockIndexTableNameResolver->execute($this->getStockId())],
                'product.sku = stock_status_index.' . IndexStructure::SKU,
                [$quantityColumnName]
            );
    }

    public function getStockId()
    {
        if (!$this->stockId) {
            $this->stockId = $this->getStockIdForCurrentWebsite->execute();
        }
        return $this->stockId;
    }
}
