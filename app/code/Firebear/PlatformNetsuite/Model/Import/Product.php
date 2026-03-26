<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Import;

use Firebear\ImportExport\Model\Import\Product\Price\Rule\ConditionFactory;
use Firebear\ImportExport\Model\Import;
use Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory;
use Magento\BundleImportExport\Model\Import\Product\Type\Bundle;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\Attribute\Backend\Sku;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Media\ConfigInterface;
use Magento\Catalog\Model\Product\Url;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Catalog\Model\ResourceModel\Product\LinkFactory;
use Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory;
use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Customer\Model\GroupFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\EntityFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\WebsiteFactory;
use Magento\Swatches\Helper\Data;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\Swatch;
use Magento\Tax\Model\ClassModel;
use Magestore\InventorySuccess\Api\StockActivity\StockChangeInterface;
use Magestore\InventorySuccess\Api\Warehouse\WarehouseRepositoryInterface;
use Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRepositoryInterface;
use Magestore\InventorySuccess\Model\Warehouse;
use Symfony\Component\Console\Output\ConsoleOutput;
use Zend\Serializer\Serializer;
use Zend_Db_Select;
use Zend_Validate_Regex;
use function array_keys;
use function array_map;
use function array_merge;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function strlen;
use function strtolower;
use function substr;
use function version_compare;

/**
 * Class Product
 * @package Firebear\PlatformNetsuite\Model\Import
 */
class Product extends \Firebear\ImportExport\Model\Import\Product
{
    /**
     * @var array
     */
    private $configurableVariations = [];

    private $attributeValuesMapping = [];

    /**
     * @param array $rowData
     *
     * @return array
     */
    public function prepareRowForDb(array $rowData)
    {
        $rowData = $this->customFieldsMapping($rowData);

        $rowData = $this->stripSlashes($rowData);

        static $lastSku = null;

        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            return $rowData;
        }

        $lastSku = $this->getCorrectSkuAsPerLength($rowData);

        if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
            $checkSku = strtolower($lastSku);
        } else {
            $checkSku = $lastSku;
        }
        if (isset($this->_oldSku[$checkSku]) && $this->_oldSku[$checkSku]) {
            $newSku = $this->skuProcessor->getNewSku($lastSku);
            if (isset($rowData[self::COL_ATTR_SET]) && !$rowData[self::COL_ATTR_SET]) {
                $rowData[self::COL_ATTR_SET] = $newSku['attr_set_code'];
            }
            if (isset($rowData[self::COL_TYPE]) && !$rowData[self::COL_TYPE]) {
                $rowData[self::COL_TYPE] = $newSku['type_id'];
            }
        }

        return $rowData;
    }

    /**
     * Custom fields mapping for changed purposes of fields and field names.
     *
     * @param array $rowData
     *
     * @return array
     */
    public function customFieldsMapping($rowData)
    {
        $rowData = $this->attributeValuesMapping($rowData);

        foreach ($this->_fieldsMap as $systemFieldName => $fileFieldName) {
            if (array_key_exists($fileFieldName, $rowData)) {
                $rowData[$systemFieldName] = $rowData[$fileFieldName];
            }
        }
        // restore data for configurable field when it is already used in Map Attributes section
        $configField = $this->_parameters['configurable_field'];

        if ($configField && !isset($rowData[$configField])) {
            $configKey = array_search($configField, $this->_fieldsMap);
            if ($configKey) {
                $rowData[$configField] = $rowData[$configKey];
            }
        }

        $rowData = $this->_parseAdditionalAttributes($rowData);
        $rowData = $this->setStockUseConfigFieldsValues($rowData);
        if ($this->_parameters['generate_url'] && isset($rowData[self::COL_NAME])) {
            $rowData = $this->generateUrlKey($rowData);
        }

        if (array_key_exists('status', $rowData)
            && $rowData['status'] != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
        ) {
            if ($rowData['status'] == 'yes') {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
            } elseif (!empty($rowData['status']) || $this->getRowScope($rowData) == self::SCOPE_DEFAULT) {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            }
        }
        foreach ($this->_imagesArrayKeys as $image) {
            if ($image != '_media_image') {
                if (isset($rowData[$image])) {
                    $rowData[$image] = trim($rowData[$image]);
                }
            }
        }

        $rowData = $this->getNetsuiteTierPrices($rowData);
        $rowData = $this->getNetsuiteWebsites($rowData);
        return $rowData;
    }

    /**
     * Parse attributes names and values string to array.
     *
     * @param array $rowData
     *
     * @return array
     */
    protected function _parseAdditionalAttributes($rowData)
    {
        if (empty($rowData['additional_attributes'])) {
            return $rowData;
        }
        try {
            $source = $this->_getSource();
        } catch (\Exception $e) {
            $source = null;
        }
        $valuePairs = explode(
            $this->getMultipleValueSeparator(),
            $rowData['additional_attributes']
        );
        foreach ($valuePairs as $valuePair) {
            $separatorPosition = strpos($valuePair, self::PAIR_NAME_VALUE_SEPARATOR);
            if ($separatorPosition !== false) {
                $key = substr($valuePair, 0, $separatorPosition);
                $value = substr(
                    $valuePair,
                    $separatorPosition + strlen(self::PAIR_NAME_VALUE_SEPARATOR)
                );
                if ($source !== null) {
                    $key = $source->changeField($key);
                }
                $multiLineSeparator = strpos($value, self::PSEUDO_MULTI_LINE_SEPARATOR);
                if ($multiLineSeparator !== false) {
                    $value = implode(
                        $this->getMultipleValueSeparator(),
                        explode(self::PSEUDO_MULTI_LINE_SEPARATOR, $value)
                    );
                }
                $rowData[$key] = $value === false ? '' : $value;
            }
        }

        return $rowData;
    }

    /**
     * Set values in use_config_ fields.
     *
     * @param array $rowData
     *
     * @return array
     */
    protected function setStockUseConfigFieldsValues($rowData)
    {
        $useConfigFields = [];
        foreach ($rowData as $key => $value) {
            if (isset($this->defaultStockData[$key])
                && isset($this->defaultStockData[self::INVENTORY_USE_CONFIG_PREFIX . $key])
                && !empty($value)
            ) {
                $useConfigFields[self::INVENTORY_USE_CONFIG_PREFIX . $key] =
                    ($value == self::INVENTORY_USE_CONFIG) ? 1 : 0;
            }
        }
        if (!empty($useConfigFields)) {
            $rowData = array_merge($rowData, $useConfigFields);
        }

        return $rowData;
    }

    /**
     * @return $this|\Magento\CatalogImportExport\Model\Import\Product
     * @throws LocalizedException
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $prevData = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $this->_importExportData->getBunchSize();

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }
        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                if (version_compare($this->productMetadata->getVersion(), '2.2.4', '>=')) {
                    $bunchRows = $this->prepareCustomOptionRows($bunchRows);
                }

                $bunchRows = $this->prepareSimpleProducts($bunchRows);

                $this->addLogWriteln(__('Saving Validated Bunches'), $this->output, 'info');
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen($this->jsonHelper->jsonEncode($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
                $this->configurableVariations = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                    $rowData = $this->prepareConfigurableVariations($rowData);

                    $invalidAttr = [];
                    foreach ($rowData as $attrName => $element) {
                        if (!is_array($element) && !mb_check_encoding($element, 'UTF-8')) {
                            unset($rowData[$attrName]);
                            $invalidAttr[] = $attrName;
                        }
                    }
                    if (!empty($invalidAttr)) {
                        $this->addRowError(
                            AbstractEntity::ERROR_CODE_ILLEGAL_CHARACTERS,
                            $this->_processedRowsCount,
                            \implode(',', $invalidAttr)
                        );
                    }
                } catch (\InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                if (isset($rowData['configurable_variations']) && $rowData['configurable_variations']) {
                    $this->checkAttributePresenceInAttributeSet($rowData);
                }
                $rowData = $this->adjustBundleTypeAttributes($rowData);
                $rowData[self::COL_SKU] = $this->getCorrectSkuAsPerLength($rowData);
                $rowData = $this->customFieldsMapping($rowData);
                $rowData = $this->customBunchesData($rowData);
                if (empty($this->getCorrectSkuAsPerLength($rowData))) {
                    $rowData = array_merge($prevData, $this->deleteEmpty($rowData));
                } else {
                    $prevData = $rowData;
                }
                $this->_processedRowsCount++;

                if ($this->onlyUpdate || $this->onlyAdd) {
                    $oldSkus = $this->skuProcessor->getOldSkus();
                    $productSku = strtolower($this->getCorrectSkuAsPerLength($rowData));

                    if (!isset($oldSkus[$productSku]) && $this->onlyUpdate) {
                        $source->next();
                        continue;
                    } elseif (isset($oldSkus[$productSku]) && $this->onlyAdd) {
                        $source->next();
                        continue;
                    }
                }

                if ($this->getBehavior() == Import::BEHAVIOR_REPLACE) {
                    if (isset($rowData['attribute_set_code'])) {
                        $rowData['_attribute_set'] = $rowData['attribute_set_code'];
                    }
                }

                if ($this->validateRow($rowData, $source->key())) {
                    // add row to bunch for save
                    $rowData = $this->_prepareRowForDb($rowData);
                    $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));

                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                    if (($rowData['sku'] !== $this->getLastSku())
                        && ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded)) {
                        $startNewBunch = true;
                        $nextRowBackup = [$source->key() => $rowData];
                    } else {
                        $bunchRows[$source->key()] = $rowData;
                        $currentDataSize += $rowSize;
                    }
                    $this->setLastSku($rowData['sku']);
                }

                $source->next();
            }
        }
        $this->getOptionEntity()->validateAmbiguousData();

        return $this;
    }

    /**
     * @param $bunchRows
     *
     * @return mixed
     */
    private function prepareCustomOptionRows($bunchRows)
    {
        $notValidRows = [];
        $validRows = [];

        foreach ($bunchRows as $rowNumber => $rowData) {
            if (empty($rowData['store_view_code']) && !empty($rowData['custom_options'])) {
                $validRows[$rowData['sku']] = true;
            } else {
                if (!empty($rowData['store_view_code']) && !empty($rowData['custom_options'])) {
                    if (!in_array($rowData['sku'], array_keys($validRows))) {
                        $notValidRows[] = $rowNumber;
                    }
                }
            }
        }

        $fixedRowData = [];

        if (!empty($notValidRows)) {
            foreach ($notValidRows as $notValidRow) {
                $fixedRow = null;
                if (strpos($bunchRows[$notValidRow]['custom_options'], 'required') !== false) {
                    $fixedRow = $bunchRows[$notValidRow];
                    $fixedRow['store_view_code'] = null;
                }
                if (!isset($fixedRowData[$bunchRows[$notValidRow]['sku']]) && $fixedRow) {
                    $fixedRowData[$bunchRows[$notValidRow]['sku']] = $fixedRow;
                }
            }
        }

        if (!empty($fixedRowData)) {
            foreach ($fixedRowData as $sku => $fixedRowItem) {
                array_unshift($bunchRows, $fixedRowItem);
            }
        }

        return $bunchRows;
    }

    /**
     * @param $bunchRows
     * @return mixed
     */
    protected function prepareSimpleProducts($bunchRows)
    {
        if ($this->_parameters['configurable_switch']) {
            foreach ($bunchRows as $rowNumber => $rowData) {
                if (!empty($rowData['options']) && isset($this->configurableVariations[$rowData['sku']])) {
                    foreach ($this->configurableVariations[$rowData['sku']] as $row) {
                        unset($row['options']);
                        $row['product_type'] = 'simple';
                        $row['visibility'] = 'Not Visible Individually';
                        if (isset($row['_media_image'])) {
                            unset($row['_media_image']);
                        }
                        array_splice($bunchRows, $rowNumber, 0, [$row]);
                    }
                }
            }
        }
        return $bunchRows;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function prepareConfigurableVariations($rowData)
    {
        $configurableVariations = '';
        if (isset($rowData['sku']) && !empty($rowData['options']) && $this->_parameters['configurable_switch']) {
            $variations = [];
            if (!empty($this->_parameters['configurable_variations'])) {
                foreach ($this->_parameters['configurable_variations'] as $attrField) {
                    foreach ($rowData['options'] as $option) {
                        $simpleRow = array_merge($rowData, $option);
                        $simpleRow = $this->customFieldsMapping($simpleRow);
                        if (isset($simpleRow[$attrField])) {
                            $variations[$simpleRow['sku']][$attrField] = $simpleRow[$attrField];
                            $this->configurableVariations[$rowData['sku']][] = $simpleRow;
                        }
                    }
                }
            }
            $isDefault = true;
            foreach ($variations as $sku => $data) {
                $configurableVariations .= 'sku=' . $sku . ',';
                foreach ($data as $attribute => $value) {
                    if (strpos($value, '-') !== false) {
                        $value = explode('-', $value)[0];
                    }

                    $configurableVariations .= $attribute . '=' . trim($value) . ',';
                }
                $configurableVariations = rtrim($configurableVariations, ',');
                if ($isDefault) {
                    $configurableVariations .= ',default=1';
                    $isDefault = null;
                }
                $configurableVariations .= '|';
            }
            $configurableVariations = rtrim($configurableVariations, '|');
        }
        if ($configurableVariations !== '') {
            $rowData['product_type'] = 'configurable';
            $rowData['configurable_variations'] = $configurableVariations;
            $rowData['attribute_set_code'] = $rowData[self::COL_ATTR_SET];
        }
        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    public function customBunchesData($rowData)
    {
        if (isset($rowData['_media_image']) && !is_array($rowData['_media_image'])) {
            $rowData['thumbnail'] = $rowData['_media_image'];
            $rowData['image'] = $rowData['_media_image'];
        }
        return $rowData;
    }

    /**
     * Retrieve image from row.
     *
     * @param array $rowData
     * @return array
     */
    public function getImagesFromRow(array $rowData)
    {
        $images = [];
        $labels = [];

        foreach ($this->_imagesArrayKeys as $column) {
            if (!empty($rowData[$column])) {
                if (!is_array($rowData[$column])) {
                    $images[$column] = array_unique(
                        array_map(
                            'trim',
                            explode($this->getMultipleValueSeparator(), $rowData[$column])
                        )
                    );
                } else {
                    $images[$column] = array_unique(
                        array_map(
                            'trim',
                            $rowData[$column]
                        )
                    );
                }

                if (!empty($rowData[$column . '_label'])) {
                    $labels[$column] = $this->parseMultipleValues($rowData[$column . '_label']);

                    if (count($labels[$column]) > count($images[$column])) {
                        $labels[$column] = array_slice($labels[$column], 0, count($images[$column]));
                    }
                }
            }
        }
        return [$images, $labels];
    }

    /**
     * Parse values from multiple attributes fields
     *
     * @param string $labelRow
     * @return array
     */
    private function parseMultipleValues($labelRow)
    {
        return $this->parseMultiselectValues(
            $labelRow,
            $this->getMultipleValueSeparator()
        );
    }

    /**
     * @param $rowData
     *
     * @return mixed
     */
    protected function applyPriceRules($rowData)
    {
        $rowData = parent::applyPriceRules($rowData);

        if (isset($rowData['special_price']) && $rowData['special_price'] == 0) {
            unset($rowData['special_price']);
        }

        return $rowData;
    }

    /**
     * @param $rowData
     *
     * @return mixed
     */
    public function applyCategoryLevelSeparator($rowData): array
    {
        $defaultCategoryName = '';
        $importCategoryName = '';
        if (isset($this->_parameters['root_category_id']) && $this->_parameters['root_category_id'] > 0) {
            $importCategoryId = (int)$this->_parameters['root_category_id'];
            /** @var \Magento\Catalog\Model\Category $importCategory */
            $importCategory = $this->categoryProcessor->getCategoryById($importCategoryId);
            if ($importCategory) {
                $parentCategoryName = $importCategory->getParentCategory()->getName();
                if ($parentCategoryName !== 'Root Catalog') {
                    $importCategoryName = $defaultCategoryName = $importCategory->getParentCategory()->getName();
                    if ((int)$importCategory->getId() === $importCategoryId) {
                        $importCategoryName .= '/' . $importCategory->getName();
                    }
                } else {
                    if ((int)$importCategory->getId() === $importCategoryId) {
                        $importCategoryName .= $importCategory->getName();
                        $defaultCategoryName = $importCategoryName;
                    }
                }
            }
        }
        if (isset($rowData[self::COL_CATEGORY]) && $rowData[self::COL_CATEGORY]) {
            $rowData[self::COL_CATEGORY] = str_replace(
                $this->_parameters['category_levels_separator'],
                '/',
                $rowData[self::COL_CATEGORY]
            );

            $rowCategories = explode('/', $rowData[self::COL_CATEGORY]);
            $finalRowCat = [];
            foreach ($rowCategories as $rowCat) {
                if ($rowCat == '') {
                    continue;
                }
                $finalRowCat[] = trim($rowCat);
            }
            $rowData[self::COL_CATEGORY] = implode('/', $finalRowCat);
        }
        $categories = [];
        if ($defaultCategoryName && $importCategoryName && isset($rowData[self::COL_CATEGORY])
            && $rowData[self::COL_CATEGORY] !== '') {
            foreach (explode($this->_parameters['categories_separator'], $rowData[self::COL_CATEGORY]) as $category) {
                if (strpos(trim($category), $defaultCategoryName) !== false) {
                    $categories[] = trim($category);
                } else {
                    $categories[] = $importCategoryName . '/' . trim($category);
                }
            }
            $rowData[self::COL_CATEGORY] = implode($this->_parameters['categories_separator'], $categories);
        } elseif ($importCategoryName) {
            $rowData[self::COL_CATEGORY] = $importCategoryName;
        }

        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function getNetsuiteWebsites($rowData)
    {
        $this->getJobMapping();

        if (!empty($rowData['product_websites'])) {
            $websiteCodes = explode($this->getMultipleValueSeparator(), $rowData['product_websites']);
            foreach ($websiteCodes as $websiteCode) {
                if (isset($this->attributeValuesMapping[$websiteCode])) {
                    $rowData['product_websites'] = str_replace(
                        $websiteCode, $this->attributeValuesMapping[$websiteCode], $rowData['product_websites']
                    );
                    $rowData[self::COL_PRODUCT_WEBSITES] = $rowData['product_websites'];
                }
            }
        }

        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function getNetsuiteTierPrices($rowData)
    {
        $this->getJobMapping();
        if (isset($rowData['price_levels']) && !empty($rowData['price_levels'])) {
            $priceLevels = json_decode($rowData['price_levels']);
            $rowData['tier_prices'] = '';
            foreach ($priceLevels as $customerGroup => $qtyPrice) {
                foreach ($qtyPrice as $qty => $price) {
                    if ($qty > 0) {
                        $rowData['tier_prices'] .= $customerGroup . ',' . $qty . ',' . $price . ',0,All|';
                    }
                }
            }
            if (!empty($rowData['tier_prices'])) {
                $rowData['tier_prices'] = substr($rowData['tier_prices'], 0, -1);
            }
        }

        return $rowData;
    }

    /**
     * @return array
     */
    protected function getJobMapping()
    {
        if (empty($this->attributeValuesMapping)) {
            $select = $this->_connection->select()->from(
                [
                    'main' => $this->getResource()->getTable('firebear_import_jobs'),
                ],
                ['mapping']
            )->where('entity_id=?', $this->_parameters['job_id']);
            $mapping = $this->_connection->fetchAll($select);

            foreach ($mapping as $map) {
                $map = \Zend\Serializer\Serializer::unserialize($map['mapping']);

                foreach ($map as $item) {
                    if (isset($item['source_data_attribute_value_system']) &&
                        isset($item['source_data_attribute_value_import'])
                    ) {
                        $this->attributeValuesMapping[$item['source_data_attribute_value_import']] =
                            $item['source_data_attribute_value_system'];
                    }
                }
            }
        }

        return $this->attributeValuesMapping;
    }

    /**
     * @param $array
     *
     * @return array
     */
    protected function deleteEmpty($array)
    {
        if (isset($array[self::COL_SKU])) {
            unset($array[self::COL_SKU]);
        }
        $newElement = [];
        foreach ($array as $key => $element) {
            if (is_array($element) || strlen($element)) {
                $newElement[$key] = $element;
            }
        }

        return $newElement;
    }

    /**
     * @param array $rowData
     * @return array
     */
    public function stripSlashes(array $rowData)
    {
        foreach ($rowData as $key => $val) {
            if ($key === '') {
                continue;
            }
            if (!empty($val) && !is_array($val)) {
                $rowData[$key] = stripslashes((string) $val);
            }
        }
        return $rowData;
    }
}
