/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
    'jquery',
    'Magento_Checkout/js/view/summary/shipping',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/step-navigator',
    'mage/translate'
], function ($, Component, quote, stepNavigator, $t) {
    'use strict';
    var mixin = {
        /**
         * @return {*}
         */
        getIncludingValue: function () {
            var price;

            if (stepNavigator.getActiveItemIndex() !== 2) {
                return $t('Not yet calculated');
            }
            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }
            price = this.totals()['shipping_incl_tax'];

            return this.getFormattedPrice(price);
        },

        /**
         * @return {*}
         */
        getExcludingValue: function () {
            var price;

            if (stepNavigator.getActiveItemIndex() !== 2) {
                return $t('Not yet calculated');
            }

            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }
            price = this.totals()['shipping_amount'];

            return this.getFormattedPrice(price);
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
