<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRuleGraphQl\Model\Resolver\CollectionProcessor;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Add attributes required for related products GraphQL resolution process.
 *
 * {@inheritdoc}
 */
class RelatedProductsProcessor implements CollectionProcessorInterface, ResetAfterRequestInterface
{
    /**
     * @var AttributeCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var AttributeCollection
     */
    private $collection;

    /**
     * @param AttributeCollectionFactory $collectionFactory
     */
    public function __construct(AttributeCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Process collection to promo rules attributes to a product collection.
     *
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributeNames
     * @param ContextInterface|null $context
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(
        Collection $collection,
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames,
        ContextInterface $context = null
    ): Collection {
        if (in_array('related_products', $attributeNames)) {
            foreach ($this->getPromoRulesAttributes() as $promoAttribute) {
                $attributeCode = $promoAttribute->getAttributeCode();
                if (!in_array($attributeCode, $attributeNames)) {
                    $collection->addAttributeToSelect($attributeCode);
                }
            }
        }

        return $collection;
    }

    /**
     * Returns promo rules attributes for products.
     *
     * @return AttributeCollection
     */
    private function getPromoRulesAttributes() : AttributeCollection
    {
        if (!$this->collection) {
            $this->collection = $this->collectionFactory->create();
            $this->collection->addFieldToFilter('is_used_for_promo_rules', '1');
        }

        return $this->collection->load();
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->collection = null;
    }
}
