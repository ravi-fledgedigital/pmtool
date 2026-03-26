<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\LayeredNavigationLiveSearch\Plugin\LiveSearch;


use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver as TableResolver;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\Request\Dimension;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;
use Magento\Framework\Module\Manager;
use Magento\SaaSCommon\Model\ExportFeed;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddExtraFiltersProductsMetadataPlugin
{
    private $extraFilterConfigProvider;

    private $resource;

    private $storeManager;

    private $stockState;

    private $objectManager;

    private $categoryRepository;

    private $tableResolver;

    private $additionalFilters;

    private $dataMappers;

    private $moduleManager;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ExtraFilterConfigProvider $extraFilterConfigProvider,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        StockRegistryInterface $stockState,
        ObjectManagerInterface $objectManager,
        CategoryRepositoryInterface $categoryRepository,
        TableResolver $tableResolver,
        Manager $moduleManager,
        array $additionalFilters,
        array $dataMappers
    ) {
        $this->extraFilterConfigProvider = $extraFilterConfigProvider;
        $this->resource                  = $resource;
        $this->storeManager              = $storeManager;
        $this->stockState                = $stockState;
        $this->objectManager             = $objectManager;
        $this->categoryRepository        = $categoryRepository;
        $this->tableResolver             = $tableResolver;
        $this->additionalFilters         = $additionalFilters;
        $this->dataMappers               = $dataMappers;
        $this->moduleManager             = $moduleManager;
    }

    /**
     * @param ExportFeed $subject
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @return FeedExportStatus
     */
    public function beforeExport(ExportFeed $subject, array $data, FeedIndexMetadata $metadata)
    {
        if (!$this->moduleManager->isEnabled('Magento_LiveSearch') || $metadata->getFeedName() != 'products') {
            return [$data, $metadata];
        }

        $data = $this->addScoresToProductMetadata($data);

        return [$data, $metadata];
    }

    private function addScoresToProductMetadata(array $data): array
    {
        foreach ($this->storeManager->getStores() as $store) {
            if ($store->getId() == 0) {
                continue;
            }
            $data = $this->addFilterValuesToProductMetadata($data, $store);
        }
        return $data;
    }

    private function getFilterParamName(string $code): ?string
    {
        $filterName = explode('mst_', $code);
        $filterCode = count($filterName) > 1 ? strtoupper($filterName[1]) : $code;
        $filterCode .= '_FILTER';
        $reflection = new \ReflectionClass(ExtraFilterConfigProvider::class);

        return $reflection->getConstant($filterCode) ?: null;
    }

    private function isRatingFilter(string $code): bool
    {
        return $code == ExtraFilterConfigProvider::RATING_FILTER_FRONT_PARAM;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function addFilterValuesToProductMetadata(array $data, StoreInterface $store): array
    {
        $ids = [];
        foreach ($data as $item) {
            if (isset($item['storeViewCode']) && $item['storeViewCode'] == $store->getCode()) {
                $ids[] = $item['productId'];
            }
        }

        $filterValuesPerProduct = [];

        foreach ($this->additionalFilters as $code => $filter) { // all but search and stock filters
            if (!isset($this->dataMappers[$code]) || $code == ExtraFilterConfigProvider::STOCK_FILTER_FRONT_PARAM) {
                continue;
            }

            $dataMapper = $this->objectManager->get($this->dataMappers[$code]);
            $values     = $this->resource->getConnection()->fetchPairs(
                $dataMapper->buildSelectQuery((int)$store->getId(), $ids)
            );

            foreach ($values as $id => $value) {
                $filterValuesPerProduct[$id][$code] = $this->isRatingFilter($code)
                    ? (float)$value
                    : (int)$value;
            }
        }

        foreach ($ids as $id) { // stock filter
            $stockStatus = $this->stockState->getStockStatus($id, $store->getWebsiteId())->getStockStatus() ? 2 : 1;
            $filterValuesPerProduct[$id][ExtraFilterConfigProvider::STOCK_FILTER_FRONT_PARAM] = $stockStatus;
        }
        
        $rootCategory   = $this->categoryRepository->get($store->getRootCategoryId(), $store->getId());
        $indexTableName = $this->getIndexTableName($store->getCode());
        
        foreach ($data as &$item) {
            foreach ($filterValuesPerProduct[$item['productId']] as $code => $value) {
                $isRatingFilter = $this->isRatingFilter($code);
    
                $item['attributes'][] = [
                    "attributeCode" => $this->getFilterParamName($code),
                    "type"          => $isRatingFilter ? "select" : "boolean",
                    "value"         => [$value],
                    "valueId"       => $isRatingFilter ? [$value] : [$value ? 1 : 0]
                ];
            }

            $rootCategoryIncluded = false;
            $categoryData         = isset($item['categoryData']) ? $item['categoryData'] : [];
    
            foreach ($categoryData as $catData) {
                if ($catData['categoryId'] == $rootCategory->getId()) {
                    $rootCategoryIncluded = true;
                    break;
                }
            }

            if (!$rootCategoryIncluded) {
                $positionQuery = "SELECT position FROM " . $indexTableName  . " WHERE category_id = " . $rootCategory->getId()
                    . " AND product_id = " . $item['productId'] . " AND store_id = " . $store->getId();

                $pos = $this->resource->getConnection()->query($positionQuery)->fetchAll();
                $pos = count($pos) ? $pos[0]['position'] : 0;

                $item['categoryData'][] = [
                    'categoryId'      => $rootCategory->getId(),
                    'categoryPath'    => $rootCategory->getUrlKey(),
                    'productPosition' => $pos
                ];
            }
        }

        return $data;
    }

    private function getIndexTableName(string $storeViewCode): string
    {
        $connection = $this->resource->getConnection();
        $storeId    = $connection->fetchOne(
            $connection->select()
                ->from(['store' => $this->resource->getTableName('store')],'store_id')
                ->where('store.code = ?', $storeViewCode)
        );
        $catalogCategoryProductDimension = new Dimension(
            \Magento\Store\Model\Store::ENTITY,
            $storeId
        );

        $tableName = $this->tableResolver->resolve(
            AbstractAction::MAIN_INDEX_TABLE,
            [$catalogCategoryProductDimension]
        );

        return $tableName;
    }
}
