<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStagingGraphQl\Model\Plugin;

use Magento\CatalogGraphQl\Model\Resolver\Cache\Product\MediaGallery\ResolverCacheIdentity;
use Magento\CatalogStaging\Model\ProductApplier;
use Magento\GraphQlResolverCache\Model\Resolver\Result\Type as GraphQlResolverCache;

/**
 * Class ProductApplierPlugin plugin of \Magento\CatalogStaging\Model\ProductApplier
 */
class ProductApplierPlugin
{
    /**
     * @var GraphQlResolverCache
     */
    private $graphQlResolverCache;

    /**
     * @param GraphQLResolverCache $graphQlResolverCache
     */
    public function __construct(GraphQLResolverCache $graphQlResolverCache)
    {
        $this->graphQlResolverCache = $graphQlResolverCache;
    }

    /**
     * Clean media gallery graphql resolver cache entries for products represented in $entityIds
     *
     * @param ProductApplier $subject
     * @param void $result
     * @param array $entityIds
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        ProductApplier $subject,
        $result,
        array $entityIds
    ) {
        $tags = array_map(function ($entityId) {
            return sprintf(
                '%s_%s',
                ResolverCacheIdentity::CACHE_TAG,
                $entityId
            );
        }, $entityIds);

        if (!empty($tags)) {
            $this->graphQlResolverCache->clean(
                \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                $tags
            );
        }
    }
}
