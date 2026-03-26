<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSyncAdminGraphQlServer\Resolver\Query;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * Resolver for DataMgmtScope
 */
class ProductCount implements ResolverInterface
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {

        $productCollection = $this->collectionFactory->create();

        if (isset($args['productCountRequest'])) {
            $request = $args['productCountRequest'];

            if (isset($request['websiteCode'])) {
                $productCollection->addWebsiteFilter($request['websiteCode']);
            }

            if (isset($request['storeViewCode'])) {
                $productCollection->addStoreFilter($request['storeViewCode']);
            }

            if (isset($request['filters']['enabled'])) {
                $productCollection = $this->addStatusFilter($request['filters']['enabled'], $productCollection);
            }

            if (isset($request['filters']['visibility'])) {
                $productCollection->addAttributeToFilter('visibility', array('in' => $this->getVisibility($request['filters']['visibility'])));
            }
        }

        return ['count' => $productCollection->getSize()];
    }

    private function getVisibility(array $visibility): array
    {
        return array_map(function($v) {
            switch ($v) {
                case "BOTH":
                    return Visibility::VISIBILITY_BOTH;

                case "NOT_VISIBLE":
                    return Visibility::VISIBILITY_NOT_VISIBLE;
                case "CATALOG":
                    return Visibility::VISIBILITY_IN_CATALOG;

                case "SEARCH":
                    return Visibility::VISIBILITY_IN_SEARCH;

                default:
                    // No Visibility Filter Added
            }
            return Visibility::VISIBILITY_NOT_VISIBLE;
        }, $visibility);

    }

    private function addStatusFilter(bool $enabled, Collection $productCollection): Collection
    {
        if ($enabled) {
            $productCollection->addAttributeToFilter('status', array('eq' => Status::STATUS_ENABLED));
        } else {
            $productCollection->addAttributeToFilter('status', array('eq' => Status::STATUS_DISABLED));
        }
        return $productCollection;
    }

}
