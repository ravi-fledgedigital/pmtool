<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Bundle\Model\Product\Type as BundleProductType;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Helper\Data as HelperData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProductType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProductType;
use NetSuite\Classes\AddRequest;
use NetSuite\Classes\ItemGroup;
use NetSuite\Classes\ItemMatrixType;
use NetSuite\Classes\ItemMember;
use NetSuite\Classes\ItemMemberList;
use NetSuite\Classes\ListOrRecordRef;
use NetSuite\Classes\MatrixOptionList;
use NetSuite\Classes\Price;
use NetSuite\Classes\PriceLevel;
use NetSuite\Classes\PriceList;
use NetSuite\Classes\Pricing;
use NetSuite\Classes\PricingMatrix;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SelectCustomFieldRef;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Product
 * @package Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway
 */
class Product
{
    use GeneralTrait;

    const BASE_PRICE_LEVEL_INTERNAL_ID = 1;

    const ENTERNAL_ID_PREFIX = 'm2';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Config data
     */
    private $config;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ReadInterface
     */
    private $directory;

    /**
     * @var array
     */
    private $behaviorData = [];

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var array
     */
    private $attributeData = [];

    /**
     * @var GroupedProductType
     */
    protected $groupedProductType;

    /**
     * @var BundleProductType
     */
    protected $bundleProductType;

    /**
     * @var ConfigurableProductType
     */
    protected $configurableProductType;

    /**
     * @var array
     */
    private $parentIdsChildItemsRelations = [];

    /**
     * @var array
     */
    private $configurableProductVariations = [];

    /**
     * @var
     */
    private $service;

    /**
     * Product constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepository $productRepository
     * @param CategoryRepository $categoryRepository
     * @param Filesystem $filesystem
     * @param Logger $logger
     * @param ConsoleOutput $output
     * @param HelperData $helperData
     * @param GroupedProductType $groupedProductType
     * @param BundleProductType $bundleProductType
     * @param ConfigurableProductType $configurableProductType
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        Filesystem $filesystem,
        Logger $logger,
        ConsoleOutput $output,
        HelperData $helperData,
        GroupedProductType $groupedProductType,
        BundleProductType $bundleProductType,
        ConfigurableProductType $configurableProductType
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->filesystem = $filesystem;
        $this->directory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->_logger = $logger;
        $this->output = $output;
        $this->helperData = $helperData;
        $this->bundleProductType = $bundleProductType;
        $this->groupedProductType = $groupedProductType;
        $this->configurableProductType = $configurableProductType;
    }

    /**
     * Set behavior data
     *
     * @param $data
     *
     * @return $this
     */
    public function setBehaviorData($data)
    {
        $this->behaviorData = $data;

        return $this;
    }

    /**
     * Get behavior data
     *
     * @return array
     */
    public function getBehaviorData()
    {
        return $this->behaviorData;
    }

    /**
     * @param $entity
     *
     * @param $offset
     * @param null $categoryId
     * @return bool|mixed
     */
    public function exportSource($data)
    {
        $fileMetadata = $this->exportProduct($data);
        if ($fileMetadata) {
            return $fileMetadata;
        } else {
            return false;
        }
    }

    /**
     * @param $data
     */
    private function exportProduct($data)
    {
        $options = [
            'connection_timeout' => 6000,
            'keep_alive' => true
        ];
        $exportConfigurableProducts = $this->behaviorData['export_configurable_product'];
        $exportBundleAsGrouped = $this->behaviorData['export_bundle_products_as_grouped_products'];
        $this->service = new \NetSuite\NetSuiteService($this->getConfig(), $options);
        if ($data['product_type'] == 'simple' || $data['product_type'] == 'virtual') {
            $this->exportSimpleProduct($data);
        } elseif ($data['product_type'] == 'configurable' && $exportConfigurableProducts) {
            $this->exportConfigurableProduct($data);
        } elseif ($data['product_type'] == 'bundle' || ($data['product_type'] == 'grouped' && $exportBundleAsGrouped)) {
            $this->exportBundleOrGroupedProduct($data);
        }
    }

    /**
     * Export Simple Products to NetSuite
     *
     * @param array $data
     * @param int|null $parentItemInternalId
     *
     * @return mixed
     */
    private function exportSimpleProduct($data, $parentItemInternalId = null)
    {
        $product = $this->productRepository->get($data['sku'], true);
        $configurableProductParentIds = $this->configurableProductType->getParentIdsByChild($product->getId());
        if (count($configurableProductParentIds) && !$parentItemInternalId) {
            foreach ($configurableProductParentIds as $configurableProductId) {
                $skuChildProducts = [];
                if (count($this->configurableProductVariations)) {
                    $skuChildProducts = array_column(
                        $this->configurableProductVariations[$configurableProductId],
                        'sku'
                    );
                }
                if (!in_array($data['sku'], $skuChildProducts)) {
                    $this->configurableProductVariations[$configurableProductId][] = $data;
                }
            }
            return false;
        }
        $inventoryItem = new \NetSuite\Classes\InventoryItem();
        $this->prepareInventoryItem($product, $inventoryItem, $data);
        $externalId = self::ENTERNAL_ID_PREFIX . '_' . $data['sku'];
        $bundleProductParentIds = $this->bundleProductType->getParentIdsByChild($product->getId());
        $groupedProductParentIds = $this->groupedProductType->getParentIdsByChild($product->getId());
        $inventoryItem->externalId = $externalId;
        if (count($bundleProductParentIds)) {
            foreach ($bundleProductParentIds as $id) {
                if (!isset($this->parentIdsChildItemsRelations[$id]) ||
                    !in_array($data['netsuite_internal_id'], $this->parentIdsChildItemsRelations[$id])) {
                    $this->parentIdsChildItemsRelations[$id][] = $data['netsuite_internal_id'];
                }
            }
        }
        if (count($groupedProductParentIds)) {
            foreach ($groupedProductParentIds as $id) {
                if (!isset($this->parentIdsChildItemsRelations[$id]) ||
                    !in_array($externalId, $this->parentIdsChildItemsRelations[$id])) {
                    $this->parentIdsChildItemsRelations[$id][] = $externalId;
                }
            }
        }
        if ($parentItemInternalId) {
            $parentRecordRef = new RecordRef();
            $parentRecordRef->type = RecordType::inventoryItem;
            $parentRecordRef->internalId = $parentItemInternalId;
            $matrixOptionList = new MatrixOptionList();
            foreach ($this->attributeData as $attributeCode => $attribute) {
                $selectCustomFieldRef = new SelectCustomFieldRef();
                $selectCustomFieldRef->scriptId = 'custitem' . $attributeCode;
                $valueIndex = $product->getData($attributeCode);
                $keyValues = array_keys($attribute['values']);
                $listOrRecordRef = new ListOrRecordRef();
                $listOrRecordRef->typeId = 1;
                $listOrRecordRef->internalId = array_search($valueIndex, $keyValues) + 1;
                $listOrRecordRef->name = $attribute['values'][$valueIndex];
                $selectCustomFieldRef->value = $listOrRecordRef;
                $matrixOptionList->matrixOption[] = $selectCustomFieldRef;
            }
            $inventoryItem->matrixOptionList = $matrixOptionList;
            $inventoryItem->parent = $parentRecordRef;
            $inventoryItem->matrixType = ItemMatrixType::_child;
            return $inventoryItem;
        }
        if ($data['netsuite_internal_id']) {
            $inventoryItem->internalId = $data['netsuite_internal_id'];
            $this->updateItemRequest($inventoryItem, $data['sku']);
        } else {
            $this->addItemRequest($inventoryItem, $data['sku']);
        }
    }

    /**
     * Export Bundle or Grouped Products to NetSuite
     *
     * @param $data
     * @return mixed
     */
    private function exportBundleOrGroupedProduct($data)
    {
        $itemGroup = new ItemGroup();
        $behaviorData = $this->getBehaviorData();
        $itemGroup->displayName = $data['name'];
        $itemGroup->description = $data['description'];
        $itemGroup->itemId = $data['sku'];
        if (!empty($data['upc'])) {
            $itemGroup->upcCode = $data['upc'];
        }
        if (!empty($behaviorData['location_internal_id'])) {
            if (!empty($behaviorData['subsidiary_internal_id'])) {
                $subsidiaryList = new \NetSuite\Classes\RecordRefList();
                $subsidiary = new \NetSuite\Classes\RecordRef();
                $subsidiary->internalId = $behaviorData['subsidiary_internal_id'];
                $subsidiaryList->recordRef = [$subsidiary];
                $itemGroup->subsidiaryList = $subsidiaryList;
            }
            $itemLocation = new \NetSuite\Classes\Location();
            $itemLocation->internalId = $behaviorData['location_internal_id'];
            $itemGroup->location = $itemLocation;
        }
        if (!empty($behaviorData['order_department_internal_id'])) {
            $department = new \NetSuite\Classes\RecordRef();
            $department->internalId = $behaviorData['order_department_internal_id'];
            $itemGroup->department = $department;
        }
        if (!empty($behaviorData['fund_internal_id'])) {
            $fund = new \NetSuite\Classes\RecordRef();
            $fund->internalId = $behaviorData['fund_internal_id'];
            $itemGroup->class = $fund;
        }
        $itemGroup->internalId = $data['netsuite_internal_id'];
        $bundleOrGroupedProduct = $this->productRepository->get($data['sku']);
        $memberList = new ItemMemberList();
        if (isset($this->parentIdsChildItemsRelations[$bundleOrGroupedProduct->getId()])) {
            foreach ($this->parentIdsChildItemsRelations[$bundleOrGroupedProduct->getId()] as $inventoryInternalItemId) {
                $itemMember = new ItemMember();
                $recordRef = new RecordRef();
                $recordRef->internalId = $inventoryInternalItemId;
                $itemMember->item = $recordRef;
                $memberList->itemMember[] = $itemMember;
            }
        }
        $itemGroup->memberList = $memberList;
        if ($data['netsuite_internal_id']) {
            $response = $this->updateItemRequest($itemGroup, $data['sku']);
        } else {
            $response = $this->addItemRequest($itemGroup, $data['sku']);
        }
        return $response;
    }

    /**
     * Export Configurable Products to NetSuite
     *
     * @param $data
     */
    private function exportConfigurableProduct($data)
    {
        $product = $this->productRepository->get($data['sku'], true);
        $productAttributes = $this->helperData->getAllowAttributes($product);
        $productAttributeItems = $productAttributes->getItems();
        $matrixItemNameTemplate = '';
        $inventoryItem = new \NetSuite\Classes\InventoryItem();
        $inventoryItem->internalId = $data['netsuite_internal_id'];
        $this->prepareInventoryItem($product, $inventoryItem, $data);
        $matrixOptionList = new MatrixOptionList();
        foreach ($productAttributeItems as $item) {
            $attributeData = $item->getProductAttribute();
            $attributeCode = $attributeData->getAttributeCode();
            $customItemField = 'customitem' . $attributeCode;
            $matrixItemNameTemplate = $matrixItemNameTemplate . '{' . $customItemField . '} ';
            $selectCustomFieldRef = new SelectCustomFieldRef();
            $selectCustomFieldRef->scriptId = $customItemField;
            $matrixOptionList->matrixOption[] = $selectCustomFieldRef;
            if (!in_array($attributeCode, $this->attributeData)) {
                $this->attributeData[$attributeCode]['label'] = $item->getLabel();
                $this->attributeData[$attributeCode]['id'] = $item->getAttributeId();
                $valueIndices = array_column($item->getOptions(), 'value_index');
                $valueLabels = array_column($item->getOptions(), 'store_label');
                $this->attributeData[$attributeCode]['values'] = array_combine($valueIndices, $valueLabels);
            }
        }
        $inventoryItem->matrixType = ItemMatrixType::_parent;
        $inventoryItem->matrixOptionList = $matrixOptionList;
        $inventoryItem->matrixItemNameTemplate = $matrixItemNameTemplate;
        $inventoryItem->matrixOptionList = new MatrixOptionList();

        if ($data['netsuite_internal_id']) {
            $parentItemInternalId = $this->updateItemRequest($inventoryItem, $data['sku']);
        } else {
            $parentItemInternalId = $this->addItemRequest($inventoryItem, $data['sku']);
        }
        if ($parentItemInternalId && isset($this->configurableProductVariations[$product->getId()])) {
            $upsertListRequest = new \NetSuite\Classes\UpsertListRequest();
            foreach ($this->configurableProductVariations[$product->getId()] as $variationData) {
                $upsertListRequest->record[] =
                    $this->exportSimpleProduct($variationData, $parentItemInternalId);
            }
            $upsertListResponse = $this->service->upsertList($upsertListRequest);
            $childWriteResponses = $upsertListResponse->writeResponseList->writeResponse;
            foreach ($childWriteResponses as $childWriteResponse) {
                if (!$childWriteResponse->status->isSuccess) {
                    $warningMessage = __(
                        'The child product not exported to the Netsuite.' .
                        ' Message: %1',
                        [
                            $childWriteResponse->status->statusDetail[0]->message
                        ]
                    );
                    $this->addLogWriteln($warningMessage, $this->output, 'warning');
                }
            }
        }
    }

    /**
     * Export Images
     *
     * @param $data
     * @return string
     * @throws FileSystemException
     */
    private function exportImage($data, $imageType)
    {
        $behaviorData = $this->getBehaviorData();
        $itemDisplayImageInternalId = '';
        if (!empty($behaviorData['image_folder_internal_id'])) {
            $image = $data[$imageType];

            if (!empty($image)) {
                $imageFile = explode('/', $image);
                $imageName = end($imageFile);
                $imageContent = $this->directory->readFile('catalog/product' . $image);
                if (!empty($imageContent)) {
                    $file = new \NetSuite\Classes\File();
                    $file->content = $imageContent;
                    $file->name = $data['sku'] . '-' . $imageName;
                    $folder = new \NetSuite\Classes\Folder();
                    $folder->internalId = $behaviorData['image_folder_internal_id'];
                    $file->folder = $folder;
                    $request = new \NetSuite\Classes\AddRequest();
                    $request->record = $file;
                    $addResponse = $this->service->add($request);
                    if ($addResponse->writeResponse->status->isSuccess) {
                        $internalId = $addResponse->writeResponse->baseRef->internalId;
                        $itemDisplayImageInternalId = $internalId;
                    }
                }
            }
        }
        return $itemDisplayImageInternalId;
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        if (empty($this->config)) {

            $behaviorData = $this->getBehaviorData();

            if (!empty($behaviorData)) {
                $this->config = [
                    "endpoint" => \trim($behaviorData['netsuite_credentials_endpoint']),
                    "host"     => \trim($behaviorData['netsuite_credentials_host']),
                    "account"  => \trim($behaviorData['netsuite_credentials_account']),
                    "consumerKey" => \trim($behaviorData['netsuite_credentials_consumer_key']),
                    "consumerSecret" => \trim($behaviorData['netsuite_credentials_consumer_secret']),
                    "token" => \trim($behaviorData['netsuite_credentials_token']),
                    "tokenSecret" => \trim($behaviorData['netsuite_credentials_token_secret']),
                    "use_old_http_protocol_version" => \trim(
                        $behaviorData['netsuite_credentials_use_old_http_protocol_version']
                    )
                ];
            } else {
                $this->config = [
                    "endpoint" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/endpoint')),
                    "host"     => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/host')),
                    "account"  => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/account')),
                    "consumerKey" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/consumerKey')),
                    "consumerSecret" => \trim(
                        $this->scopeConfig->getValue('firebear_importexport/netsuite/consumerSecret')
                    ),
                    "token" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/token')),
                    "tokenSecret" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/tokenSecret')),
                    "use_old_http_protocol_version" => \trim(
                        $this->scopeConfig->getValue(
                            'firebear_importexport/netsuite/use_old_http_protocol_version'
                        )
                    )
                ];
            }
        }
        return $this->config;
    }

    /**
     * Prepare Inventory Item for export
     * @param $product
     * @param $inventoryItem
     * @param $data
     * @return mixed
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    private function prepareInventoryItem($product, $inventoryItem, $data)
    {
        $behaviorData = $this->getBehaviorData();
        $inventoryItem->displayName = (!empty($data['name'])) ? $data['name'] : 'Test name';
        $inventoryItem->storeDisplayName = (!empty($data['name'])) ? $data['name'] : 'Test name';
        $inventoryItem->storeDescription = isset($data['short_description']) ? $data['short_description'] : '';
        $inventoryItem->storeDetailedDescription = isset($data['description']) ? $data['description'] : '';
        $inventoryItem->itemId = $data['sku'];
        if (!empty($data['upc'])) {
            $inventoryItem->upcCode = $data['upc'];
        }
        if (!empty($data['weight_grams'])) {
            $inventoryItem->weight = $data['weight_grams'];
        }
        $pricingMatrix = new PricingMatrix();
        if (!empty($behaviorData['currency_internal_id'])) {
            $currency = new RecordRef();
            $currency->type = RecordType::currency;
            $currency->internalId = $behaviorData['currency_internal_id'];
        }
        $tierPrices = $product->getData('tier_price');
        if ($behaviorData['export_advanced_pricing'] && $tierPrices) {
            $priceLevelObjects = [];
            $pricingObjects = [];
            $pricingMatrixData = [];
            $allPriceLevelIds = explode(',', $behaviorData['netsuite_price_level_ids']);
            $setDefQtyForPriceLevel = [];
            if (isset($behaviorData['netsuite_customer_price_level_map'])) {
                foreach ($tierPrices as $tierPrice) {
                    foreach ($behaviorData['netsuite_customer_price_level_map'] as $priceLevelMap) {
                        if ($priceLevelMap['behavior_field_netsuite_customer_price_level_map_customer_group'] == $tierPrice['cust_group']) {
                            $priceLevelInternalId = $priceLevelMap['behavior_field_netsuite_customer_price_level_map_price_level_id'];
                            break;
                        }
                    }
                    if (!in_array($tierPrice['price_qty'], $setDefQtyForPriceLevel)) {
                        foreach ($allPriceLevelIds as $priceLevelId) {
                            $setDefQtyForPriceLevel[] = $tierPrice['price_qty'];
                            $pricingMatrixData[] = [$behaviorData['currency_internal_id'], $priceLevelId, $product->getPrice(), $tierPrice['price_qty']];
                        }
                    }
                    if (!in_array(0, $setDefQtyForPriceLevel)) {
                        foreach ($allPriceLevelIds as $priceLevelId) {
                            $setDefQtyForPriceLevel[] = 0;
                            $pricingMatrixData[] = [$behaviorData['currency_internal_id'], $priceLevelId, $product->getPrice(), 0];
                        }
                    }

                    $pricingMatrixData[] = [$behaviorData['currency_internal_id'], $priceLevelInternalId, $tierPrice['price'], $tierPrice['price_qty']];

                    if (!key_exists($priceLevelInternalId, $priceLevelObjects)) {
                        $newPriceLevel = new RecordRef();
                        $newPriceLevel->type = RecordType::priceLevel;
                        $newPriceLevel->internalId = $priceLevelInternalId;
                        $priceLevelObjects[$priceLevelInternalId] = $newPriceLevel;
                    }
                    if (!key_exists($priceLevelInternalId, $pricingObjects)) {
                        $newPricing = new Pricing();
                        $newPricing->priceLevel = $priceLevelObjects[$priceLevelInternalId];
                        if (isset($currency)) {
                            $newPricing->currency = $currency;
                        }

                        $price = new Price();
                        $price->quantity = $tierPrice['price_qty'];
                        $price->value = $tierPrice['price'];

                        $newPriceList = new PriceList();
                        $newPriceList->price[] = $price;

                        $newPricing->priceList = $newPriceList;
                        $pricingObjects[$priceLevelInternalId] = $newPricing;
                    } else {
                        $existingPricing = $pricingObjects[$priceLevelInternalId];
                        $price = new Price();
                        $price->quantity = $tierPrice['price_qty'];
                        $price->value = $tierPrice['price'];
                        $existingPricing->priceList->price[] = $price;
                        $pricingObjects[$priceLevelInternalId] = $existingPricing;
                    }
                }
                $pricingMatrix->replaceAll = true;
                foreach ($pricingMatrixData as $priceMatrix) {
                    $currency = new \NetSuite\Classes\RecordRef();
                    $currency->type = RecordType::currency;
                    $currency->internalId = $behaviorData['currency_internal_id'];
                    $price = new \NetSuite\Classes\Price();
                    $price->value = $priceMatrix[2];
                    $price->quantity = $priceMatrix[3];
                    $priceList = new PriceList();
                    $priceList->price[] = $price;
                    $priceLevel = new PriceLevel();
                    $priceLevel->internalId = $priceMatrix[1];
                    $pricing = new \NetSuite\Classes\Pricing();
                    $pricing->priceList = $priceList;
                    $pricing->priceLevel = $priceLevel;
                    $pricing->currency = $currency;
                    $pricingMatrix->pricing[] = $pricing;
                }
            }
        } else {
            $price = new \NetSuite\Classes\Price();
            $price->value = $data['price'];
            $price->quantity = 0;
            $priceList = new PriceList();
            $priceList->price = $price;
            $priceLevel = new PriceLevel();
            $priceLevel->internalId = self::BASE_PRICE_LEVEL_INTERNAL_ID;
            $pricing = new \NetSuite\Classes\Pricing();
            $pricing->priceList = $priceList;
            $pricing->priceLevel = $priceLevel;
            if (isset($currency)) {
                $pricing->currency = $currency;
            }
            $pricingMatrix = new PricingMatrix();
            $pricingMatrix->pricing = $pricing;
            $pricingMatrix->replaceAll = false;
        }
        $inventoryItem->pricingMatrix = $pricingMatrix;

        if (!empty($behaviorData['tax_schedule_internal_id'])) {
            $taxSchedule = new \NetSuite\Classes\RecordRef();
            $taxSchedule->internalId = $behaviorData['tax_schedule_internal_id'];
            $inventoryItem->taxSchedule = $taxSchedule;
        }
        if (!empty($behaviorData['order_department_internal_id']) && !$data['netsuite_internal_id']) {
            $department = new \NetSuite\Classes\RecordRef();
            $department->internalId = $behaviorData['order_department_internal_id'];
            $inventoryItem->department = $department;
        }

        if (!empty($behaviorData['fund_internal_id']) && !$data['netsuite_internal_id']) {
            $fund = new \NetSuite\Classes\RecordRef();
            $fund->internalId = $behaviorData['fund_internal_id'];
            $inventoryItem->class = $fund;
        }

        if (!empty($behaviorData['sales_tax_code_internal_id'])) {
            $salesTaxCode = new \NetSuite\Classes\RecordRef();
            $salesTaxCode->internalId = $behaviorData['sales_tax_code_internal_id'];
            $inventoryItem->salesTaxCode = $salesTaxCode;
        }
        $categoryIds = $product->getCategoryIds();

        if (!empty($categoryIds)) {
            $siteCategories = [];
            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryRepository->get($categoryId);
                if ($category) {
                    $netsuiteCategoryInternalId = $category->getData('netsuite_internal_id');
                    if (!empty($netsuiteCategoryInternalId)) {
                        $siteCategory = new \NetSuite\Classes\RecordRef();
                        $siteCategory->internalId = $netsuiteCategoryInternalId;
                        $siteCategories[] = $siteCategory;
                    }
                }
            }
        }
        if (!empty($siteCategories)) {
            $siteCategoryList = new \NetSuite\Classes\SiteCategoryList();
            $siteCategoryList->replaceAll = false;

            foreach ($siteCategories as $siteCategory) {
                $siteCategoryAccounting = new \NetSuite\Classes\SiteCategory_accounting();
                $siteCategoryAccounting->category = $siteCategory;
                $siteCategoryList->siteCategory[] = $siteCategoryAccounting;
            }

            $inventoryItem->siteCategoryList = $siteCategoryList;
        }
        if (!empty($siteCategoryList)) {
            $inventoryItem->siteCategoryList = $siteCategoryList;
        }
        if ($this->behaviorData['export_images']) {
            $itemDisplayImageInternalId = $this->exportImage($data, 'base_image');
            if (!empty($itemDisplayImageInternalId)) {
                $itemDisplayImage = new \NetSuite\Classes\RecordRef();
                $itemDisplayImage->internalId = $itemDisplayImageInternalId;
                $inventoryItem->storeDisplayImage = $itemDisplayImage;
            }

            $itemDisplayThumbnailImageInternalId = $this->exportImage($data, 'thumbnail_image');
            if (!empty($itemDisplayThumbnailImageInternalId)) {
                $itemDisplayImage = new \NetSuite\Classes\RecordRef();
                $itemDisplayImage->internalId = $itemDisplayThumbnailImageInternalId;
                $inventoryItem->storeDisplayThumbnail = $itemDisplayImage;
            }
        }
        if (!empty($behaviorData['location_internal_id']) && !$data['netsuite_internal_id']) {
            if (!empty($behaviorData['subsidiary_internal_id'])) {
                $subsidiaryList = new \NetSuite\Classes\RecordRefList();
                $subsidiary = new \NetSuite\Classes\RecordRef();
                $subsidiary->internalId = $behaviorData['subsidiary_internal_id'];
                $subsidiaryList->recordRef = [$subsidiary];
                $inventoryItem->subsidiaryList = $subsidiaryList;
            }

            $inventoryItemLocation = new \NetSuite\Classes\Location();
            $inventoryItemLocation->internalId = $behaviorData['location_internal_id'];
            $inventoryItem->location = $inventoryItemLocation;
            $location = new \NetSuite\Classes\Location();
            $location->internalId = $behaviorData['location_internal_id'];
            $locations = new \NetSuite\Classes\InventoryItemLocations();
            $locations->locationId = $location;
            $locations->quantityOnHand = $data['qty'];
            $locationList = new \NetSuite\Classes\InventoryItemLocationsList();
            $locationList->locations = $locations;
            $locationList->replaceAll = false;
            $inventoryItem->locationsList = $locationList;
        }
        return $inventoryItem;
    }

    /**
     * @param $item
     * @param $sku
     * @return integer|boolean
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function addItemRequest($item, $sku)
    {
        $addRequest = new AddRequest();
        $addRequest->record = $item;
        $response = $this->service->add($addRequest);
        $statusResponse = $response->writeResponse->status->isSuccess;
        if ($statusResponse) {
            $this->addSuccessLogMessage($sku);
            $internalId = $response->writeResponse->baseRef->internalId;
            $product = $this->productRepository->get($sku, true);
            $product->setData('netsuite_internal_id', $internalId);
            $this->productRepository->save($product);
            return $internalId;
        } else {
            $this->addFailLogMessage($response, $sku);
            return false;
        }
    }

    /**
     * @param $item
     * @param $sku
     * @return integer|boolean
     */
    private function updateItemRequest($item, $sku)
    {
        $request = new \NetSuite\Classes\UpdateRequest();
        $request->record = $item;
        $response = $this->service->update($request);
        $statusResponse = $response->writeResponse->status->isSuccess;
        if ($statusResponse) {
            $this->addSuccessLogMessage($sku);
            $internalId = $response->writeResponse->baseRef->internalId;
            return $internalId;
        } elseif ($response->writeResponse->status->statusDetail[0]->code == 'INVALID_KEY_OR_REF') {
            return $this->addItemRequest($item, $sku);
        } else {
            $this->addFailLogMessage($response, $sku);
            return false;
        }
    }

    /**
     * @param $sku
     */
    private function addSuccessLogMessage($sku)
    {
        $successMessage = __(
            'The product  %1 was successfully imported to Netsuite',
            $sku
        );
        $this->addLogWriteln($successMessage, $this->output);
    }

    /**
     * @param $response
     * @param $sku
     */
    private function addFailLogMessage($response, $sku)
    {
        $errorMessage = __(
            'The Product not exported to the Netsuite.' .
            ' Sku: %1. Message: %2',
            [
                $sku,
                $response->writeResponse->status->statusDetail[0]->message
            ]
        );
        $this->addLogWriteln($errorMessage, $this->output, 'error');
    }
}
