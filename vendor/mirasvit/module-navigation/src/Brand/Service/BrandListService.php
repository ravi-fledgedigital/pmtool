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

namespace Mirasvit\Brand\Service;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Model\Config\GeneralConfig;
use Mirasvit\Brand\Model\Config\Source\BrandSliderOrder;
use Mirasvit\Brand\Repository\BrandPageRepository;
use Mirasvit\Brand\Repository\BrandRepository;
use Mirasvit\Brand\Service\BrandAttributeService;

class BrandListService
{
    private        $brandRepository;

    private        $brandPageRepository;

    private        $brandAttributeService;

    private        $productCollectionFactory;

    private        $storeManager;

    private        $config;

    private ?array $brandsByLetters = null;

    public function __construct(
        BrandRepository       $brandRepository,
        BrandPageRepository   $brandPageRepository,
        BrandAttributeService $brandAttributeService,
        CollectionFactory     $productCollectionFactory,
        StoreManagerInterface $storeManager,
        GeneralConfig         $config
    ) {
        $this->brandRepository          = $brandRepository;
        $this->brandPageRepository      = $brandPageRepository;
        $this->brandAttributeService    = $brandAttributeService;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager             = $storeManager;
        $this->config                   = $config;
    }

    /**
     * Return collection of brands grouped by first letter.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getBrandsByLetters(?array $usedBrands = null): array
    {
        if (!is_null($this->brandsByLetters)) {
            return $this->brandsByLetters;
        }

        $collectionByLetters = [];
        $collection          = $this->getBrandCollection($usedBrands);

        foreach ($collection as $brand) {
            $label = $brand->getLabel();

            $letter = strtoupper(mb_substr(trim($label), 0, 1));

            if (isset($collectionByLetters[$letter])) {
                $collectionByLetters[$letter][$label] = $brand;
            } else {
                $collectionByLetters[$letter] = [$label => $brand];
            }
        }

        // sort brands alphabetically
        ksort($collectionByLetters);
        foreach ($collectionByLetters as $letter => $brands) {
            ksort($brands);
            $collectionByLetters[$letter] = $brands;
        }

        $this->brandsByLetters = $collectionByLetters;

        return $collectionByLetters;
    }

    public function getBrandAlphabet(): array
    {
        return array_keys($this->getBrandsByLetters());
    }

    public function getBrandCollection(?array $usedBrands = null): array
    {
        $collection = $this->brandRepository->getList();

        if (count($collection) === 0) {
            return [];
        }

        if ($this->config->isShowBrandsWithoutProducts()) {
            return $collection;
        }

        $usedBrands = $usedBrands ?? $this->getUsedBrands();

        if (!$usedBrands) {
            return [];
        }

        $brandCollection = [];
        foreach ($collection as $brand) {
            if (in_array($brand->getId(), $usedBrands)) {
                $brandCollection[] = $brand;
            }
        }

        return $brandCollection;
    }

    public function getFeaturedBrands(): array
    {
        $brands = [];
        foreach($this->getSliderItems() as $brandPage){
            $brands[] = $this->brandRepository->get($brandPage->getAttributeOptionId());
        }

        return $brands;
    }

    public function getSliderItems(int $orderBy = BrandSliderOrder::SLIDER_TITLE_ORDER): array
    {
         $usedBrands = $this->getUsedBrands();

        if (!$usedBrands) {
            return [];
        }

        $attributeId = $this->brandAttributeService->getBrandAttributeId();

        $collection = $this->brandPageRepository->getCollection()
            ->addStoreFilter((int)$this->storeManager->getStore()->getId())
            ->addFieldToFilter(BrandPageInterface::ATTRIBUTE_ID, $attributeId)
            ->addFieldToFilter(BrandPageInterface::IS_SHOW_IN_BRAND_SLIDER, 1)
            ->addFieldToFilter(BrandPageInterface::IS_ACTIVE, 1);

        if ($orderBy === BrandSliderOrder::SLIDER_POSITION_ORDER) {
            $collection->setOrder(BrandPageInterface::SLIDER_POSITION, 'asc');
        } else {
            $collection->setOrder(BrandPageInterface::BRAND_TITLE, 'asc');
        }

        $existItems = [];

        foreach ($collection as $brandPage) {
            if (
                in_array($brandPage->getAttributeOptionId(), $usedBrands)
                && $this->brandRepository->get($brandPage->getAttributeOptionId())
            ) {
                $existItems[] = $brandPage;
            }
        }

        return $existItems;
    }

    private function getUsedBrands(): array
    {
        $brandAttribute = $this->brandAttributeService->getAttribute();

        if (!$brandAttribute) {
            return [];
        }

        $brandAttributeCode = $brandAttribute->getAttributeCode();
        $options            = $brandAttribute->getOptions();

        $brandOptionIds = [];
        foreach ($options as $option) {
            $brandOptionIds[] = $option->getValue();
        }

        $productCollection = $this->productCollectionFactory->create();

        if ($productCollection->isEnabledFlat()) {
            $connection = $productCollection->getConnection();
            $mainTable  = $productCollection->getMainTable();

            if ($connection->tableColumnExists($mainTable, $brandAttributeCode)) {
                $productCollection->addAttributeToSelect($brandAttributeCode)
                    ->getSelect()
                    ->group('e.' . $brandAttributeCode);
            } else {
                $attributeId   = (int)$brandAttribute->getAttributeId();
                $eavTable      = $productCollection->getResource()->getTable('catalog_product_entity_int');
                $joinCondition = sprintf(
                    'brand_eav.entity_id = e.entity_id AND brand_eav.attribute_id = %d AND brand_eav.store_id = 0',
                    $attributeId
                );

                $productCollection->getSelect()
                    ->join(['brand_eav' => $eavTable], $joinCondition, [$brandAttributeCode => 'brand_eav.value'])
                    ->group('brand_eav.value');
            }
        } else {
            $productCollection->addAttributeToSelect($brandAttributeCode)
                ->addAttributeToFilter($brandAttributeCode, ['notnull' => true])
                ->getSelect()
                ->group($brandAttributeCode);
        }

        $usedBrands = [];
        foreach ($productCollection as $product) {
            $optionId = $product->getData($brandAttributeCode);
            if (in_array($optionId, $brandOptionIds) && !in_array($optionId, $usedBrands)) {
                $usedBrands[] = $optionId;
            }
        }

        return $usedBrands;
    }
}
