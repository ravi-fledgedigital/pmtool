<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPProductFileExport\Model\Export;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductEntity;
use Magento\Catalog\Model\ProductFactory as productModelFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\CatalogImportExport\Model\Export\Product;
use Magento\CatalogImportExport\Model\Export\ProductFilterInterface;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\FlagManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Vaimo\AEPFileExport\Model\ExportEntityInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AEPProduct extends Product implements ExportEntityInterface
{
    // phpcs:ignore
    /**
     * @var string[]
     */
    protected $_fieldsMap = [
        self::COL_STORE => 'store_view_code',
        self::COL_ATTR_SET => 'attribute_set_code',
        self::COL_TYPE => 'product_type',
        self::COL_CATEGORY => 'categories',
        self::COL_PRODUCT_WEBSITES => 'product_websites',
    ];

    /**
     * @var MappingInterface
     */
    private MappingInterface $mapping;

    /**
     * @var StoreManagerInterface
     */
    public StoreManagerInterface $storeManager;

    /**
     * @var ProductFilterInterface
     */
    private ?ProductFilterInterface $filter;

    /**
     * @var FlagManager
     */
    private FlagManager $flagManager;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var FlagManager
     */
    private ?string $lastRunFlagCode;

    /**
     * @var \Vaimo\AepBase\Helper\Data
     */
    private $baseHelper;

    /**
     * @var productModelFactory
     */
    private $productModelFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Configurable
     */
    private $productTypeConfigurable;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    private $storeManagerRepo;

    /**
     * @var \Vaimo\OTScene7Integration\Model\ConfigProvider
     */
    private $secene7Config;

    /**
     * @var StoreManagerInterface
     */
    protected $storemanager;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * Constructor
     *
     * @param TimezoneInterface $localeDate
     * @param Config $config
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CollectionFactory $collectionFactory
     * @param ConfigInterface $exportConfig
     * @param ProductFactory $productFactory
     * @param AttributeSetCollectionFactory $attrSetColFactory
     * @param CategoryCollectionFactory $categoryColFactory
     * @param ItemFactory $itemFactory
     * @param ProductCollectionFactory $optionColFactory
     * @param AttributeCollectionFactory $attributeColFactory
     * @param Product\Type\Factory $typeFactory
     * @param ProductEntity\LinkTypeProvider $linkTypeProvider
     * @param RowCustomizerInterface $rowCustomizer
     * @param MappingInterface $mapping
     * @param ?ProductFilterInterface $filter
     * @param FlagManager $flagManager
     * @param DateTime $dateTime
     * @param \Vaimo\AepBase\Helper\Data $baseHelper
     * @param productModelFactory $productModelFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $productTypeConfigurable
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeManagerRepo
     * @param \Vaimo\OTScene7Integration\Model\ConfigProvider $secene7Config
     * @param array $dateAttrCodes
     * @param string $lastRunFlagCode
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Config $config,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        CollectionFactory $collectionFactory,
        ConfigInterface $exportConfig,
        ProductFactory $productFactory,
        AttributeSetCollectionFactory $attrSetColFactory,
        CategoryCollectionFactory $categoryColFactory,
        ItemFactory $itemFactory,
        ProductCollectionFactory $optionColFactory,
        AttributeCollectionFactory $attributeColFactory,
        Product\Type\Factory $typeFactory,
        ProductEntity\LinkTypeProvider $linkTypeProvider,
        RowCustomizerInterface $rowCustomizer,
        MappingInterface $mapping,
        ?ProductFilterInterface $filter,
        FlagManager $flagManager,
        DateTime $dateTime,
        \Vaimo\AepBase\Helper\Data $baseHelper,
        productModelFactory $productModelFactory,
        ProductRepositoryInterface $productRepository,
        Configurable $productTypeConfigurable,
        \Magento\Store\Api\StoreRepositoryInterface $storeManagerRepo,
        \Vaimo\OTScene7Integration\Model\ConfigProvider $secene7Config,
        array $dateAttrCodes = [],
        ?string $lastRunFlagCode = 'aep_products_export_last_run'
    ) {
        parent::__construct(
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $logger,
            $collectionFactory,
            $exportConfig,
            $productFactory,
            $attrSetColFactory,
            $categoryColFactory,
            $itemFactory,
            $optionColFactory,
            $attributeColFactory,
            $typeFactory,
            $linkTypeProvider,
            $rowCustomizer,
            $dateAttrCodes,
            $filter
        );
        $this->mapping = $mapping;
        $this->filter = $filter;
        $this->flagManager = $flagManager;
        $this->lastRunFlagCode = $lastRunFlagCode;
        $this->dateTime = $dateTime;
        $this->baseHelper = $baseHelper;
        $this->storemanager = $storeManager;
        $this->productFactory = $productFactory;
        $this->productModelFactory = $productModelFactory;
        $this->productRepository = $productRepository;
        $this->productTypeConfigurable = $productTypeConfigurable;
        $this->storeManagerRepo = $storeManagerRepo;
        $this->secene7Config = $secene7Config;
    }

    /**
     * Export product
     *
     * @return obj|string
     */
    public function export(): string
    {
        //Execution time may be very long
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        set_time_limit(0);

        $writer = $this->getWriter();
        $page = 0;
        $lastRunDate = !empty($this->flagManager->getFlagData($this->lastRunFlagCode)) ?
            $this->flagManager->getFlagData($this->lastRunFlagCode) :
            '';
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->addAttributeToSelect(['brands', 'qa_size']);
            $entityCollection->addAttributeToFilter(
                'status',
                \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
            );
            $entityCollection->setOrder('entity_id', 'asc');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $this->_prepareEntityCollection($entityCollection, $lastRunDate);
            $this->paginateCollection($page, $this->getItemsPerPage());

            if ($entityCollection->count() == 0) {
                break;
            }

            $exportData = $this->getExportData();

            if ($page == 1) {
                $headerColumns = array_diff($this->_getHeaderColumns(), $this->getSkipAttributeArray());
                array_push($headerColumns, 'brand_name');
                array_push($headerColumns, 'image');
                array_push($headerColumns, 'status');
                $writer->setHeaderCols($headerColumns);
            }
            foreach ($exportData as $dataRow) {
                $writer->writeRow($this->_customFieldsMapping($dataRow));
            }

            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        $this->flagManager->saveFlag($this->lastRunFlagCode, $this->dateTime->date());

        return $writer->getContents();
    }

    /**
     * [_prepareEntityCollection description]
     *
     * @param  AbstractCollection $collection  [description]
     * @param  string             $lastRunDate [description]
     * @return [type]                          [description]
     */
    protected function _prepareEntityCollection(AbstractCollection $collection, string $lastRunDate = ''): Collection
    {
        $exportFilter = !empty($this->_parameters[Export::FILTER_ELEMENT_GROUP]) ?
            $this->_parameters[Export::FILTER_ELEMENT_GROUP] : [];
        $rawCollection = $this->filter->filter($collection, $exportFilter);
        $collection = parent::_prepareEntityCollection($rawCollection);
        /*$collection->addFieldToFilter('sku', ['in' => ['2183B171.700.L']]);*/
        if (!empty($lastRunDate)) {
            $collection->addFieldToFilter('updated_at', ['gteq' => $lastRunDate]);
        }

        return $collection;
    }

    /**
     * Get Export data of products
     *
     * @return string[][]
     * @throws \Exception
     */
    public function getExportData(): array
    {
        $exportData = [];
        $rawData = $this->collectRawData();

        foreach ($rawData as $productData) {
            $globalData = $productData[0] ?? [];
            foreach ($productData as $dataRow) {
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
                $exportData[] = array_merge($globalData, $dataRow);
            }
        }

        $result = $this->changeData($exportData);
        $this->_processedRowsCount += count($result);

        return $result;
    }

    /**
     * Collect the Raw Data of the product
     *
     * @return string[][][]
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function collectRawData(): array
    {
        $data = [];
        $items = $this->loadCollection();

        $storeCodes = $this->baseHelper->getExcludeStoreProducts();
        $storeCodesArray = [];
        if (!empty($storeCodes)) {
            $storeCodesArray = explode(',', $storeCodes);
        }

        /**
         * Initialize the product data generation
         *
         * @var int $itemId
         * @var ProductEntity[] $itemByStore
         */

        $stores = $this->storeManagerRepo->getList();
        $websiteStoreCodes = [];
        foreach ($stores as $store) {
            $websiteStoreCodes[$store->getWebsiteId()][] = $store->getCode();
        }

        foreach ($items as $itemId => $itemByStore) {
            foreach ($this->_storeIdToCode as $storeId => $storeCode) {
                $item = $itemByStore[$storeId];
                $currencyCode = $this->baseHelper->getStoreById($storeId);
                $product = $this->productModelFactory->create()->load($item->getId());

                $storeCodeArr = [];

                foreach ($product->getWebsiteIds() as $websiteId) {
                    $tempArr = $websiteStoreCodes[$websiteId];
                    // phpcs:ignore
                    $storeCodeArr = array_merge($storeCodeArr, $tempArr);
                }

                $productUrl = $this->getProductUrl($item, $storeId);

                if (empty($item->getName()) || empty($productUrl)
                    || (in_array($storeCode, $storeCodesArray)
                    || !in_array($storeCode, $storeCodeArr))) {
                    continue;
                }

                foreach ($this->_getExportAttrCodes() as $code) {
                    $attrValue = $item->getData($code);
                    if (!$this->isValidAttributeValue($code, $attrValue)) {
                        continue;
                    }

                    if (isset($this->_attributeValues[$code][$attrValue]) && !empty($this->_attributeValues[$code])) {
                        $attrValue = $this->_attributeValues[$code][$attrValue];
                    }

                    $fieldName = $this->_fieldsMap[$code] ?? $code;

                    if ($storeId != Store::DEFAULT_STORE_ID
                        && isset($data[$itemId][Store::DEFAULT_STORE_ID][$fieldName])
                        && $data[$itemId][Store::DEFAULT_STORE_ID][$fieldName] == htmlspecialchars_decode($attrValue)
                    ) {
                        continue;
                    }

                    if (!is_scalar($attrValue)) {
                        continue;
                    }

                    $data[$itemId][$storeId][$fieldName] = htmlspecialchars_decode($attrValue);
                }

                $data[$itemId][$storeId][self::COL_STORE] = $storeCode;
                $data[$itemId][$storeId][self::COL_SKU] = htmlspecialchars_decode($item->getSku());
                $data[$itemId][$storeId]['product_id'] = $itemId;
                $data[$itemId][$storeId]['product_url'] = $productUrl;
                $data[$itemId][$storeId]['product_type'] = $item->getTypeId();
                $data[$itemId][$storeId]['brand_name'] = (!empty($item->getAttributeText('brands')))
                ? $item->getAttributeText('brands') : null;
                $data[$itemId][$storeId]['image'] = '';
                $data[$itemId][$storeId]['status'] = $item->getStatus();
                $data[$itemId][$storeId]['currencyCode'] = $currencyCode;
                $data[$itemId][$storeId]['size'] = (!empty($item->getAttributeText('qa_size')))
                ? $item->getAttributeText('qa_size') : $item->getQaSize();

                $scene7Images = $item->getData('scene7_available_image_angles');
                if (is_string($scene7Images)) {
                    $productGroup = $item->getAttributeText('product_group');
                    $angelsMappings = $this->secene7Config->getAnglesMapping();
                    $productTypesMapping = $this->secene7Config->getProductTypesMapping();
                    $image = $imageAngel = $iAngel = '';
                    foreach ($angelsMappings as $angel) {
                        if (isset($angel['product_type']) &&
                            $angel['product_type'] == $productGroup &&
                            isset($angel['role']) &&
                            $angel['role'] == 'image'
                        ) {
                            $imageAngel = $angel['angle'];
                            break;
                        }
                    }

                    $productImageAngel = [];

                    foreach ($productTypesMapping as $pTypeMapping) {
                        if (isset($pTypeMapping['product_type']) &&
                            $pTypeMapping['product_type'] == $productGroup &&
                            !empty($pTypeMapping['asset_angle']) &&
                            !empty($imageAngel) &&
                            str_contains($pTypeMapping['asset_angle'], $imageAngel)
                        ) {
                            $productImageAngel[$pTypeMapping['priority']] = $pTypeMapping['asset_angle'];
                        }
                    }
                    if (!empty($productImageAngel)) {
                        ksort($productImageAngel);
                        $iAngel = reset($productImageAngel);
                    }

                    $scene7Images = json_decode($item->getData('scene7_available_image_angles'), true);
                    if (!empty($iAngel) && isset($scene7Images[$iAngel])) {
                        $data[$itemId][$storeId]['image'] = $scene7Images[$iAngel];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * [getProductUrl description]
     *
     * @param  ProductEntity $product [description]
     * @param  [type]        $storeId [description]
     * @return [type]                 [description]
     */
    private function getProductUrl(ProductEntity $product, $storeId): ?string
    {
        $configProductId = $this->productTypeConfigurable->getParentIdsByChild($product->getId());
        $collection = $this->_entityCollectionFactory->create();
        $collection->addStoreFilter($storeId);
        $configProduct = $collection->addAttributeToFilter('entity_id', ['in' => $configProductId])->getFirstItem();

        $productId = $configProductUrl = '';
        if ($configProduct && $configProduct->getId()) {
            $productId = $configProduct->getId();
        } else {
            return $configProductUrl;
        }

        if ($productId) {
            $configProduct = $this->productRepository->getById($productId, false, $storeId);
            $configProductUrl = $configProduct->getUrlModel()->getUrl($configProduct);

            if (strpos($configProductUrl, "catalog/product/view/id/$productId/s/") !== false) {
                $baseUrl =  $this->storemanager->getStore($storeId)->getBaseUrl();
                $configProductUrl = $baseUrl . $configProduct->getUrlKey();
            }
        }

        return $configProductUrl;
    }

    /**
     * Get export attribute codes
     *
     * @return string[]
     */
    protected function _getExportAttrCodes(): array
    {
        $attributes = [];
        foreach ($this->mapping->getMapping() as $item) {
            if ($item['type'] !== MappingInterface::MAPPING_TYPE_ATTRIBUTE) {
                continue;
            }

            $attributes[] = $item['value'];
        }

        $attributes[] = 'visibility';
        $attributes[] = 'category_ids';
        $attributes[] = 'description';
        $attributes[] = 'updated_at';
        $attributes[] = 'url_key';
        $attributes[] = 'style_code';
        $attributes[] = 'scene7_available_image_angles';

        return $attributes;
    }

    /**
     * Mapping data to change
     *
     * @param string[][] $data
     * @return string[][]
     */
    public function changeData(array $data): array
    {
        $result = [];
        foreach ($data as $sourceItem) {
            $item = [];
            foreach ($this->mapping->getMapping() as $colName => $mapItem) {
                if (in_array($colName, $this->getSkipAttributeArray())) {
                    continue;
                }

                if ($mapItem['type'] === MappingInterface::MAPPING_TYPE_ATTRIBUTE) {
                    $item[$colName] = $sourceItem[$mapItem['value']] ?? null;
                }

                if ($mapItem['type'] !== MappingInterface::MAPPING_TYPE_CALLBACK) {
                    continue;
                }

                $callbackName = $mapItem['value'];
                $item[$colName] = method_exists($this->mapping, $callbackName)
                    ? $this->mapping->$callbackName($sourceItem)
                    : null;
            }

            $item['product_url'] = str_replace("\r\n", "", $item['product_url']);
            $item['productName'] = trim($item['productName']);
            $item['brand_name'] = $sourceItem['brand_name'];
            $item['image'] = $sourceItem['image'];
            $item['size'] = $sourceItem['size'];
            $item['status'] = ($sourceItem['status'] == 1) ? 'Enabled' : 'Disabled';
            $item['currencyCode'] = $sourceItem['currencyCode'];

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Skip prodcut attributes
     *
     * @return string[]
     */
    private function getSkipAttributeArray()
    {
        return [
            "parentSku",
            "productSubType1",
            "productSubType2",
            "productKidsType",
            "productFeatures",
            "productFabric",
            "sleeveType",
            "productMaterial",
            "shoeStyle",
            "veganFlag",
            "modifiedDate",
            "image"
        ];
    }

    /**
     * Get header columns of the csv file
     *
     * @return string[]
     */
    public function _getHeaderColumns(): array
    {
        return array_keys(
            $this->mapping->getMapping()
        );
    }
}
