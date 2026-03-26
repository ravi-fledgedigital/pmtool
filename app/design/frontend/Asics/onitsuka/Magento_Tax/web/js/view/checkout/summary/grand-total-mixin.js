/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/step-navigator'
], function (Component, quote, priceUtils, totals, stepNavigator) {
    'use strict';

    var mixin = {
        getValue: function () {
            var price = 0;

            if (this.totals()) {
                price = totals.getSegment('grand_total').value;

                if (stepNavigator.getActiveItemIndex() !== 2) {
                    price = totals.getSegment('grand_total').value - totals.getSegment('shipping').value;
                }
            }
            return this.getFormattedPrice(price);
        }
    };

    return function (target) {
        return target.extend(mixin);
    };

});
