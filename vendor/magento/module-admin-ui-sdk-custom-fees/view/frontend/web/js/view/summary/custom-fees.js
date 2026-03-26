/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote'
], function (Component, quote) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_AdminUiSdkCustomFees/checkout/cart/totals/custom-fees'
        },

        getCustomFees: function () {
            let customFees = []
            const self = this
            quote.totals._latestValue.total_segments.forEach(function (fee) {
                if (fee.code.startsWith('adminuisdk_')) {
                    const feeComponent = {
                        getTitle: function () {
                            return fee.title
                        },
                        getAmount: function () {
                            return self.getFormattedPrice(fee.value)
                        }
                    };
                    customFees.push(feeComponent);
                }
            })
            return customFees
        }
    })
})
