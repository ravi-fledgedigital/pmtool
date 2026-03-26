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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\Sorting\Plugin\LiveSearch;


use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\CatalogDataExporter\Model\Provider\ProductMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Sorting\Api\Data\CriterionInterface;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Magento\Framework\Module\Manager;
use Mirasvit\Sorting\Api\Data\RankingFactorInterface;
use Mirasvit\Sorting\Repository\RankingFactorRepository;
use Magento\Framework\Filesystem\DirectoryList;
use Mirasvit\Sorting\Model\ConfigProvider;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PutAttributeMetadataPlugin
{
    private $criterionRepository;

    private $resource;

    private $storeManager;

    private $moduleManager;

    private $rankingFactorRepository;

    private $directoryList;

    private $configProvider;

    private $stop = false;

    public function __construct(
        CriterionRepository $criterionRepository,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        RankingFactorRepository $rankingFactorRepository,
        DirectoryList $directoryList,
        ConfigProvider $configProvider
    ) {
        $this->criterionRepository      = $criterionRepository;
        $this->resource                 = $resource;
        $this->storeManager             = $storeManager;
        $this->moduleManager            = $moduleManager;
        $this->rankingFactorRepository = $rankingFactorRepository;
        $this->directoryList           = $directoryList;
        $this->configProvider          = $configProvider;
    }

    /**
     * @param FeedIndexMetadata $metadata
     * @param array $values
     * @return array
     */
    public function afterGet(ProductMetadata $subject, array $values)
    {
        if ($this->moduleManager->isEnabled('Magento_LiveSearch')) {
            $values = $this->addFactorsAndCriteriaAsAttributeToMetadata($values);
        }

        if ($this->configProvider->isDebugProductMetadataEnabled() && !$this->stop) {
            $logFileName = $this->directoryList->getRoot() . '/var/log/product-attribute-metadata.txt';
            file_put_contents($logFileName, print_r($values, true).PHP_EOL, FILE_APPEND);
        }
        $this->stop = true;
        return $values;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function addFactorsAndCriteriaAsAttributeToMetadata(array $values): array
    {
        // populate sorting scores only to first array
        if ($this->stop) {
            return $values;
        }

        $query = "SELECT attribute_id FROM " . $this->resource->getTableName('catalog_eav_attribute') . " ORDER BY attribute_id DESC LIMIT 1";

        $result = $this->resource->getConnection()
            ->query($query)
            ->fetchAll();
        
        if (!empty($result)) {
            $lastId = $result[0]['attribute_id'];
            $newId = (int)$lastId + 10;
        } else {
            $newId = 10;
        }  

        $stores = $this->storeManager->getStores();

        // add glodal sorting attribute per store
        foreach ($stores as $store) {
            if ($store->getId() == 0) {
                continue;
            }

            $values[] = $this->getPseudoAttributeMetadata(
                (string)$newId,
                "sorting_global",
                "Sorting Global",
                $this->storeManager->getGroup($store->getStoreGroupId())->getCode(),
                $this->storeManager->getWebsite($store->getWebsiteId())->getCode(),
                $store->getCode(),
                (string)$store->getId()
            );
        }

        $newId++;

        $collection = $this->rankingFactorRepository->getCollection()
            ->addFieldToFilter(RankingFactorInterface::IS_ACTIVE, true);

        // add criterion attribute by store
        foreach ($collection as $factor) {
            foreach ($stores as $store) {
                if ($store->getId() == 0) {
                    continue;
                }

                $values[] = $this->getPseudoAttributeMetadata(
                    (string)$newId,
                    'sorting_factor_' . $factor->getId(),
                    $factor->getName(),
                    $this->storeManager->getGroup($store->getStoreGroupId())->getCode(),
                    $this->storeManager->getWebsite($store->getWebsiteId())->getCode(),
                    $store->getCode(),
                    (string)$store->getId()
                );
            }

            $newId++;
        }

        // add frames score

        $criterias = $this->criterionRepository->getCollection()
            ->addFieldToFilter(CriterionInterface::IS_ACTIVE, 1);
        
        foreach ($criterias as $criterion) {
            foreach ($criterion->getConditionCluster()->getFrames() as $frameIdx => $frame) {
                if (count($frame->getNodes()) < 2) {
                    continue;
                }
                $values[] = $this->getPseudoAttributeMetadata(
                    (string)$newId,
                    'sorting_criterion_' . $criterion->getId() . '_frame_' . $frameIdx,
                    $criterion->getName() . '_frame_' . $frameIdx,
                    $this->storeManager->getGroup($store->getStoreGroupId())->getCode(),
                    $this->storeManager->getWebsite($store->getWebsiteId())->getCode(),
                    $store->getCode(),
                    (string)$store->getId()
                );
                $newId++;
            }
        }

        return $values;
    }

    private function getPseudoAttributeMetadata(
        string $newId,
        string $code,
        string $label,
        string $storeCode,
        string $websiteCode,
        string $storeViewCode,
        string $storeId
    ): array {

        return [
            "storeViewCode"        => $storeViewCode,
            "attribute_label_id"   => null,
            "attribute_id"         => null,
            "store_id"             => $storeId,
            "value"                => null,
            "id"                   => $newId,
            "attributeCode"        => $code,
            "entityTypeId"         => "4",
            "dataType"             => "decimal",
            "validation"           => null,
            "multi"                => "0",
            "frontendInput"        => "text",
            "label"                => $label,
            "required"             => "0",
            "unique"               => "0",
            "global"               => "0",
            "visible"              => "1",
            "searchable"           => "1",
            "filterable"           => "1",
            "visibleInCompareList" => "1",
            "visibleInListing"     => "0",
            "sortable"             => "1",
            "visibleInSearch"      => "0",
            "filterableInSearch"   => "0",
            "searchWeight"         => "1",
            "usedForRules"         => "0",
            "systemAttribute"      => "0",
            "storeCode"            => $storeCode,
            "websiteCode"          => $websiteCode,
            "boolean"              => false,
            "numeric"              => true,
            "attributeType"        => "catalog_product"
        ];        
    }
}
