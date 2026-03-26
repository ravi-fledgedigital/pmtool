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

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Brand\Api\Data\BrandInterface;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Repository\BrandPageRepository;

class BrandAttributeService
{
    private $config;

    private $productAttributeRepository;

    private $brandPageRepository;

    private $storeManager;

    private $brandPagesByOptions                  = [];

    private $brandPagesByOptionsFromAllStores     = [];

    private ?ProductAttributeInterface $attribute = null;

    public function __construct(
        Config                              $config,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        BrandPageRepository                 $brandPageRepository,
        StoreManagerInterface               $storeManager
    ) {
        $this->config                     = $config;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->brandPageRepository        = $brandPageRepository;
        $this->storeManager               = $storeManager;
    }

    public function getBrandAttributeId(): ?int
    {
        $attribute = $this->getAttribute();

        return $attribute ? (int)$attribute->getAttributeId() : null;
    }

    public function getVisibleBrandOptions(?int $storeId = null): array
    {
        $attribute = $this->getAttribute();

        if (!$attribute) {
            return [];
        }

        $brandPages     = $this->getBrandPagesByOptions($storeId);
        $visibleOptions = [];

        foreach ($this->getBrandOptions() as $option) {
            $page = $brandPages[$option[BrandInterface::ID]] ?? null;

            if ($this->canShowNotConfiguredOption($option) || $page) {
                $option[BrandInterface::PAGE]           = $page;
                $option[BrandInterface::ATTRIBUTE_ID]   = $attribute->getId();
                $option[BrandInterface::ATTRIBUTE_CODE] = $attribute->getAttributeCode();

                $visibleOptions[] = $option;
            }
        }

        return $visibleOptions;
    }

    public function getAllBrandOptions(?int $storeId = null): array
    {
        $attribute = $this->getAttribute();

        if (!$attribute) {
            return [];
        }

        $brandPages = $this->getBrandPagesByOptions($storeId);
        $options    = [];

        foreach ($this->getBrandOptions() as $option) {
            $page = $brandPages[$option[BrandInterface::ID]] ?? null;

            if ($page === null) {
                $page = $this->brandPageRepository->create()
                    ->setAttributeOptionId((int)$option[BrandInterface::ID]);
            }

            $option[BrandInterface::PAGE]           = $page;
            $option[BrandInterface::ATTRIBUTE_ID]   = $attribute->getId();
            $option[BrandInterface::ATTRIBUTE_CODE] = $attribute->getAttributeCode();

            $options[] = $option;
        }

        return $options;
    }

    /**
     * Get attribute used as the brand.
     */
    public function getAttribute(): ?ProductAttributeInterface
    {
        if (!$this->attribute) {
            $attributeCode   = $this->config->getGeneralConfig()->getBrandAttribute();
            $this->attribute = $attributeCode ? $this->productAttributeRepository->get($attributeCode) : null;
        }

        return $this->attribute;
    }

    private function getBrandOptions(): array
    {
        $options = $this->getAttribute()->getSource()->getAllOptions();

        foreach ($options as $idx => $option) {
            if (!$option['value'] || !$option['label']) {
                unset($options[$idx]);
            }
        }

        return $options;
    }

    public function getBrandPagesByOptions(?int $storeId = null): array
    {
        if (!$this->brandPagesByOptions && $this->getAttribute()) {
            $brandPageCollection = $this->brandPageRepository->getCollection()
                ->addStoreFilter($this->storeManager->getStore($storeId))
                ->addFieldToFilter(BrandPageInterface::ATTRIBUTE_ID, $this->getAttribute()->getId())
                ->addFieldToFilter(BrandPageInterface::IS_ACTIVE, 1);

            /** @var BrandPageInterface $item */
            foreach ($brandPageCollection as $item) {
                $this->brandPagesByOptions[$item->getAttributeOptionId()] = $item;
            }
        }

        return $this->brandPagesByOptions;
    }

    private function canShowNotConfiguredOption(array $option): bool
    {
        if (!$this->config->getGeneralConfig()->isShowNotConfiguredBrands()) {
            return false;
        }

        if (!$this->brandPagesByOptionsFromAllStores) {
            $brandPageCollection = $this->brandPageRepository->getCollection()
                ->addFieldToFilter(BrandPageInterface::ATTRIBUTE_ID, $this->getAttribute()->getId())
                ->addFieldToFilter(BrandPageInterface::IS_ACTIVE, 1);

            /** @var BrandPageInterface $item */
            foreach ($brandPageCollection as $item) {
                $this->brandPagesByOptionsFromAllStores[$item->getAttributeOptionId()] = $item;
            }
        }

        return !isset($this->brandPagesByOptionsFromAllStores[$option['value']]);
    }
}
