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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Model\Indexer\LandingPageProduct;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Search\Api\SearchInterface;
use Magento\Store\Model\App\Emulation;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Mirasvit\LandingPage\Repository\PageRepository;
use Psr\Log\LoggerInterface;

class ProductResolver
{
    private const MAX_SEARCH_RESULTS = 10000;

    private const MAX_CATALOG_PRODUCTS = 100000;

    private $productCollectionFactory;

    private $filterRepository;

    private $pageRepository;

    private $search;

    private $searchCriteriaBuilder;

    private $filterBuilder;

    private $appEmulation;

    private $resource;

    private $logger;

    private $visibilityAttributeId = null;

    private $statusAttributeId = null;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        FilterRepository         $filterRepository,
        PageRepository           $pageRepository,
        SearchInterface          $search,
        SearchCriteriaBuilder    $searchCriteriaBuilder,
        FilterBuilder            $filterBuilder,
        Emulation                $appEmulation,
        ResourceConnection       $resource,
        LoggerInterface          $logger
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->filterRepository         = $filterRepository;
        $this->pageRepository           = $pageRepository;
        $this->search                   = $search;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->filterBuilder            = $filterBuilder;
        $this->appEmulation             = $appEmulation;
        $this->resource                 = $resource;
        $this->logger                   = $logger;
    }

    public function resolve(PageInterface $page, int $storeId): array
    {
        $page = $this->pageRepository->get((int)$page->getPageId(), $storeId);

        if (!$page) {
            return [];
        }

        $searchTerm = trim((string)$page->getSearchTerm());

        if ($searchTerm !== '') {
            return $this->resolveBySearchTerm($searchTerm, $page, $storeId);
        }

        return $this->resolveByCatalog($page, $storeId);
    }

    private function resolveByCatalog(PageInterface $page, int $storeId): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addStoreFilter($storeId);

        $categories = trim((string)$page->getCategories());
        if ($categories !== '') {
            $categoryIds = array_filter(explode(',', $categories), 'strlen');
            if (!empty($categoryIds)) {
                $allCategoryIds = $this->resolveChildCategories(array_map('intval', $categoryIds));
                $collection->addCategoriesFilter(['in' => $allCategoryIds]);
            }
        }

        $filters = $this->filterRepository->getByPageId((int)$page->getPageId());
        foreach ($filters as $filter) {
            $optionIds = array_filter(explode(',', (string)$filter->getOptionIds()), 'strlen');
            if (!empty($optionIds)) {
                $collection->addFieldToFilter(
                    $filter->getAttributeCode(),
                    ['in' => $optionIds]
                );
            }
        }

        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);

        $collection->setPageSize(self::MAX_CATALOG_PRODUCTS);

        $ids = $collection->getAllIds(self::MAX_CATALOG_PRODUCTS);

        if (count($ids) >= self::MAX_CATALOG_PRODUCTS) {
            $this->logger->warning(sprintf(
                'Landing page #%d matched %d+ products (limit: %d). Some products may be missing from the index.',
                $page->getPageId(),
                self::MAX_CATALOG_PRODUCTS,
                self::MAX_CATALOG_PRODUCTS
            ));
        }

        $ids = array_map('intval', $ids);

        return $this->resolveVisibleProducts($ids);
    }

    private function resolveChildCategories(array $categoryIds): array
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName('catalog_category_entity');

        $paths = $connection->fetchCol(
            $connection->select()
                ->from($table, ['path'])
                ->where('entity_id IN (?)', $categoryIds)
        );

        if (empty($paths)) {
            return $categoryIds;
        }

        $orConditions = [];
        foreach ($paths as $path) {
            $orConditions[] = $connection->quoteInto('path LIKE ?', $path . '/%');
            $orConditions[] = $connection->quoteInto('path = ?', $path);
        }

        $allIds = $connection->fetchCol(
            $connection->select()
                ->from($table, ['entity_id'])
                ->where(implode(' OR ', $orConditions))
        );

        return array_unique(array_map('intval', $allIds));
    }

    // Replace NOT_VISIBLE simple variants with their visible parent products.
    private function resolveVisibleProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $visAttrId  = $this->getAttributeId('visibility');
        $statusAttrId = $this->getAttributeId('status');

        if (!$visAttrId) {
            return $productIds;
        }

        $notVisibleIds = $connection->fetchCol(
            $connection->select()
                ->from($this->resource->getTableName('catalog_product_entity_int'), ['entity_id'])
                ->where('entity_id IN (?)', $productIds)
                ->where('attribute_id = ?', $visAttrId)
                ->where('store_id = ?', 0)
                ->where('value = ?', Visibility::VISIBILITY_NOT_VISIBLE)
        );
        $notVisibleIds = array_map('intval', $notVisibleIds);

        $visibleIds = array_diff($productIds, $notVisibleIds);

        if (empty($notVisibleIds)) {
            return $productIds;
        }

        $parentIds = $connection->fetchCol(
            $connection->select()
                ->from($this->resource->getTableName('catalog_product_super_link'), ['parent_id'])
                ->where('product_id IN (?)', $notVisibleIds)
        );
        $parentIds = array_map('intval', $parentIds);

        if (!empty($parentIds) && $statusAttrId) {
            $validParentIds = $connection->fetchCol(
                $connection->select()
                    ->from(['vis' => $this->resource->getTableName('catalog_product_entity_int')], ['vis.entity_id'])
                    ->join(
                        ['st' => $this->resource->getTableName('catalog_product_entity_int')],
                        'st.entity_id = vis.entity_id AND st.attribute_id = ' . $statusAttrId . ' AND st.store_id = 0',
                        []
                    )
                    ->where('vis.entity_id IN (?)', $parentIds)
                    ->where('vis.attribute_id = ?', $visAttrId)
                    ->where('vis.store_id = ?', 0)
                    ->where('vis.value IN (?)', [
                        Visibility::VISIBILITY_IN_CATALOG,
                        Visibility::VISIBILITY_IN_SEARCH,
                        Visibility::VISIBILITY_BOTH,
                    ])
                    ->where('st.value = ?', Status::STATUS_ENABLED)
            );
            $parentIds = array_map('intval', $validParentIds);
        }

        return array_values(array_unique(array_merge($visibleIds, $parentIds)));
    }

    private function getAttributeId($attributeCode)
    {
        if ($attributeCode === 'visibility' && $this->visibilityAttributeId !== null) {
            return $this->visibilityAttributeId;
        }

        if ($attributeCode === 'status' && $this->statusAttributeId !== null) {
            return $this->statusAttributeId;
        }

        $connection = $this->resource->getConnection();
        $id = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('eav_attribute'), ['attribute_id'])
                ->where('attribute_code = ?', $attributeCode)
                ->where('entity_type_id = ?', 4)
        );

        $result = $id ? (int)$id : null;

        if ($attributeCode === 'visibility') {
            $this->visibilityAttributeId = $result;
        } elseif ($attributeCode === 'status') {
            $this->statusAttributeId = $result;
        }

        return $result;
    }

    private function resolveBySearchTerm(string $searchTerm, PageInterface $page, int $storeId): array
    {
        $productIds = [];

        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        try {
            $this->filterBuilder->setField('search_term');
            $this->filterBuilder->setValue($searchTerm);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());

            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchCriteria->setRequestName('quick_search_container');
            $searchCriteria->setPageSize(self::MAX_SEARCH_RESULTS);
            $searchCriteria->setSortOrders([]);
            $searchResult = $this->search->search($searchCriteria);

            foreach ($searchResult->getItems() as $item) {
                $productIds[] = (int)$item->getId();
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Landing page #%d search term indexing failed: %s',
                $page->getPageId(),
                $e->getMessage()
            ));
        } finally {
            $this->appEmulation->stopEnvironmentEmulation();
        }

        $categories = trim((string)$page->getCategories());
        $filters    = $this->filterRepository->getByPageId((int)$page->getPageId());

        if (($categories !== '' || $filters->getSize() > 0) && !empty($productIds)) {
            $catalogIds = $this->resolveByCatalog($page, $storeId);
            $productIds = array_values(array_intersect($productIds, $catalogIds));
        }

        return $productIds;
    }
}
