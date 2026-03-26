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
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Indexer\FeedIndexProcessorCreateUpdate;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver as TableResolver;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\Request\Dimension;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;
use Magento\Framework\Module\Manager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddExtraFiltersAttributesMetadataPlugin
{
    private $extraFilterConfigProvider;

    private $resource;

    private $storeManager;

    private $stockState;

    private $objectManager;

    private $categoryRepository;

    private $tableResolver;

    private $additionalFilters;

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
        array $additionalFilters
    ) {
        $this->extraFilterConfigProvider = $extraFilterConfigProvider;
        $this->resource                  = $resource;
        $this->storeManager              = $storeManager;
        $this->stockState                = $stockState;
        $this->objectManager             = $objectManager;
        $this->categoryRepository        = $categoryRepository;
        $this->tableResolver             = $tableResolver;
        $this->additionalFilters         = $additionalFilters;
        $this->moduleManager             = $moduleManager;
    }

    /**
     * @param FeedIndexProcessorCreateUpdate $subject
     * @param mixed $output
     * @param FeedIndexMetadata $metadata
     * @return mixed
     */
    public function afterFullReindex(FeedIndexProcessorCreateUpdate $subject, $output, FeedIndexMetadata $metadata)
    {
        if (!$this->moduleManager->isEnabled('Magento_LiveSearch')) {
            return $output;
        }

        if ($metadata->getFeedName() == 'productAttributes') {
            $this->addExtraFiltersAsAttributesToMetadata($metadata);
        }

        return $output;
    }

    private function addExtraFiltersAsAttributesToMetadata(FeedIndexMetadata $metadata): void
    {
        $query = "SELECT id FROM " . $this->resource->getTableName($metadata->getFeedTableName()) . " ORDER BY id DESC LIMIT 1";

        $result = $this->resource->getConnection()
            ->query($query)
            ->fetchAll();
        
        if (!empty($result)) {
            $lastId = $result[0]['id'];
            $newId = (int)$lastId + 10;
        } else {
            $newId = 10;
        }   

        $stores = $this->storeManager->getStores();

        foreach ($this->additionalFilters as $code => $filter) {
            if ($code == 'search') { // ignore search filter for now
                continue;
            }

            foreach ($stores as $store) {
                if ($store->getId() == 0) {
                    continue;
                }

                if ($filterParam = $this->getFilterParamName($code)) {
                    $this->addPseudoAttributeMetadata(
                        $metadata->getFeedTableName(),
                        (string)$newId,
                        $filterParam,
                        $this->getFilterLabel($code),
                        $this->storeManager->getGroup($store->getStoreGroupId())->getCode(),
                        $this->storeManager->getWebsite($store->getWebsiteId())->getCode(),
                        $store->getCode(),
                        $metadata->getDbDateTimeFormat()
                    );
                }
            }

            $newId++;
        }
    }

    private function getFilterParamName(string $code): ?string
    {
        $filterCode = strtoupper($code) . '_FILTER';
        $reflection = new \ReflectionClass(ExtraFilterConfigProvider::class);

        return $reflection->getConstant($filterCode) ?: null;
    }

    private function getFilterLabel(string $filterCode): string
    {
        $method = 'get' . $this->extraFilterConfigProvider->transformToMethod($filterCode) . 'FilterLabel';

        if (!method_exists($this->extraFilterConfigProvider, $method)) {
            throw new LocalizedException(__('Filter type "%1" does not exist', $filterCode));
        }

        return $this->extraFilterConfigProvider->{$method}();
    }

    private function addPseudoAttributeMetadata(
        string $tableName,
        string $id,
        string $code,
        string $label,
        string $storeCode,
        string $websiteCode,
        string $storeViewCode,
        $modifiedAtFormat
    ): void {
        $keys = ['id', 'feed_data', 'is_deleted'];
        $modifiedAt = (new \DateTime())->format($modifiedAtFormat);
        $isRatingFilter = $this->isRatingFilter($code);
        $feedData = [
            "id"                   => $id,
            "storeCode"            => $storeCode,
            "websiteCode"          => $websiteCode,
            "storeViewCode"        => $storeViewCode,
            "attributeCode"        => $code,
            "attributeType"        => "catalog_product",
            "dataType"             => "int",
            "multi"                => false,
            "label"                => $label,
            "frontendInput"        => $isRatingFilter ? "select" : "boolean",
            "required"             => false,
            "unique"               => false,
            "global"               => false,
            "visible"              => true,
            "searchable"           => false,
            "filterable"           => true,
            "visibleInCompareList" => false,
            "visibleInListing"     => false,
            "sortable"             => false,
            "visibleInSearch"      => false,
            "filterableInSearch"   => true,
            "searchWeight"         => 1,
            "usedForRules"         => false,
            "boolean"              => $isRatingFilter ? false : true,
            "systemAttribute"      => true,
            "numeric"              => $isRatingFilter ? true : false,
            "attributeOptions"     => $isRatingFilter ? [0,1,2,3,4,5] : null,
            "modified_at"          => $modifiedAt
        ];

        $this->resource->getConnection()->insertOnDuplicate(
            $this->resource->getTableName($tableName),
            [
                'id'              => $id,
                'feed_data'       => SerializeService::encode($feedData),
                'is_deleted'      => 0
            ],
            $keys
        );
    }

    private function isRatingFilter(string $code): bool
    {
        return $code == ExtraFilterConfigProvider::RATING_FILTER_FRONT_PARAM;
    }

}
