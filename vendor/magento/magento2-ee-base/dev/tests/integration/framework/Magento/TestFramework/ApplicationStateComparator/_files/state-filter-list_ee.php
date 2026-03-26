<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

return [
    'all' => [ // Note: These will be applied to all services
    ],
    'parents' => [ // Note: these are parent classes and will match their children as well.
        Magento\CustomAttributeManagement\Helper\Data::class => [
            '_userDefinedAttributeCodes' => null, // FIXME: needs _resetState
        ],
    ],
    'services' => [ // Note: These apply only to the service names that match.
        Magento\Staging\Model\Preview\RouteParamsPreprocessor::class => ['request' => null],
        Magento\Staging\Model\Url\BaseUrlModifier::class => ['request' => null, 'state' => null],
        Magento\Staging\Plugin\Store\Model\StoreResolver::class => ['request' => null],
        Magento\VersionsCmsUrlRewriteGraphQl\Plugin\UrlRewriteGraphQl\Model\UrlRewrite\HierarchyNodeUrlLocator::class
            => ['contextFactory' => null],
    ],
];
