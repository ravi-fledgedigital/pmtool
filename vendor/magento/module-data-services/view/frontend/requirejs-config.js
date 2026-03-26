/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    paths: {
        dataServicesBase: [
            'https://acds-events.adobe.io/v7/ds.min',
            'Magento_DataServices/js/noopDs'
        ],
        dataServicesDiscount: [
            'https://acds-events.adobe.io/v7/discount.min',
            'Magento_DataServices/js/noopDiscount'
        ],
        magentoStorefrontEvents: [
            'https://unpkg.com/@adobe/magento-storefront-events-sdk@^1/dist/index',
            'https://cdn.jsdelivr.net/npm/@adobe/magento-storefront-events-sdk@1/dist/index',
            'Magento_DataServices/js/noopSdk'
        ],
        magentoStorefrontEventCollector: [
            'https://unpkg.com/@adobe/magento-storefront-event-collector@^1/dist/index',
            'https://cdn.jsdelivr.net/npm/@adobe/magento-storefront-event-collector@1/dist/index',
            'Magento_DataServices/js/noopCollector'
        ]
    }
};
