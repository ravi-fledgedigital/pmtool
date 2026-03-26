<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Model\Product;
use Magento\CatalogStaging\Model\Product\DateAttributesMetadata;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Store;

/**
 * Observer to update datetime attributes related to product entity.
 *
 * Specified attributes disabled on product form for Staging, but should be synchronized with update entity date range.
 * This needs to be moved to a plugin as soon as we replace with repository in catalog save controller
 */
class UpdateProductDateAttributes implements ObserverInterface
{
    /**
     * @param TimezoneInterface $localeDate
     * @param DateAttributesMetadata $dateAttributesMetadata
     * @param ScopeOverriddenValue $scopeOverriddenValue
     */
    public function __construct(
        private TimezoneInterface $localeDate,
        private DateAttributesMetadata $dateAttributesMetadata,
        private ScopeOverriddenValue $scopeOverriddenValue,
    ) {
    }

    /**
     * Set start date and end date for datetime product attributes
     *
     * The method gets object with \Magento\Catalog\Api\Data\ProductInterface interface and updates datetime
     * attributes of this object ("start date" attributes: news_from_date, special_from_date, custom_design_from;
     *  "end date" attributes: news_to_date, special_to_date, custom_design_to).
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $this->updateAttributes(
            $product,
            $this->dateAttributesMetadata->getStartDateAttributes(),
            $this->localeDate->date()->format(DateTime::DATETIME_PHP_FORMAT),
        );
        $this->updateAttributes(
            $product,
            $this->dateAttributesMetadata->getEndDateAttributes(),
            null
        );
    }

    /**
     * Update datetime attributes
     *
     * @param Product $product
     * @param array $attributes
     * @param string|null $time
     * @return void
     */
    private function updateAttributes(Product $product, array $attributes, ?string $time): void
    {
        foreach ($attributes as $attributeCode) {
            $relatedAttributeCode = $this->dateAttributesMetadata->getRelatedAttribute($attributeCode);
            $emptyValues = $this->dateAttributesMetadata->getRelatedAttributeEmptyValues($attributeCode);
            if (!$product->hasData($relatedAttributeCode)) {
                continue;
            }

            if (in_array($product->getData($relatedAttributeCode), $emptyValues, true)) {
                // Remove the date attribute value if the related attribute value is empty
                $product->setData($attributeCode, null);
            } elseif (!$product->dataHasChangedFor($attributeCode)
                && in_array($product->getOrigData($relatedAttributeCode), $emptyValues, true)
            ) {
                // Only update if the date attribute has not changed
                // and the related attribute has changed from empty to non-empty value
                $product->setData($attributeCode, $time);
            } elseif ($this->isAttributeValueInheritedFromGlobalScope($product, $attributeCode)) {
                // If the date attribute value is inherited from global scope, prevent store view overriding
                $product->setData($attributeCode, null);
            }
        }
    }

    /**
     * Checks whether the attribute value is inherited from global scope
     *
     * @param Product $product
     * @param string $attributeCode
     * @return bool
     */
    private function isAttributeValueInheritedFromGlobalScope(Product $product, string $attributeCode): bool
    {
        return !$product->isObjectNew()
            && ((int)$product->getStoreId()) !== Store::DEFAULT_STORE_ID
            && !$product->dataHasChangedFor($attributeCode)
            && !$this->scopeOverriddenValue->containsValue(
                ProductInterface::class,
                $product,
                $attributeCode,
                $product->getStoreId()
            );
    }
}
