/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_GoogleTagManager/js/google-analytics-universal'
], function (GaUniversal) {
    'use strict';

    describe('GoogleTagManager/js/google-analytics-universal', function () {
        var ga, config;

        config = {
            blockNames: [
                'category.products.list',
                'product.info.upsell',
                'catalog.product.related',
                'checkout.cart.crosssell',
                'search_result_list'
            ],
            dlCurrencyCode: 'EUR',
            dataLayer: [],
            staticImpressions: {
                'catalog.product.related': [{
                    category: '',
                    id: 'Test1',
                    list: 'Related Products',
                    listPosition: '0',
                    name: 'Test1',
                    position: '1',
                    type: 'simple'
                },
                    {
                        category: '',
                        id: 'Test2',
                        list: 'Related Products',
                        listPosition: '0',
                        name: 'Test2',
                        position: '2',
                        type: 'simple'
                    }
                ],
                'category.products.list': [
                    {
                        category: '',
                        id: 'Test3',
                        list: 'List',
                        listPosition: '0',
                        name: 'Test3',
                        position: '1',
                        type: 'simple'
                    }
                ]
            },
            staticPromotions: [],
            updatedImpressions: [],
            updatedPromotions: []
        };

        beforeEach(function () {
            ga = new GaUniversal(config);
        });

        it('Check updateImpressions dataLayer', function () {
            ga.updateImpressions();
            expect(ga.dataLayer[0].ecommerce.impressions.length === 3).toBe(true);
        });
    });
});
