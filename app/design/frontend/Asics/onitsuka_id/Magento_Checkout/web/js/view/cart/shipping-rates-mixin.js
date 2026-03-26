/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'underscore',
    'uiComponent',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/checkout-data'
], function (ko, _, Component, shippingService, priceUtils, quote, selectShippingMethodAction, checkoutData) {
    'use strict';
    var mixin = {
        setActiveMethod: function(method) {
            if (quote.shippingMethod() && quote.shippingMethod().carrier_code == method.carrier_code) {
                return true;
            } else if (!quote.shippingMethod() && method.carrier_code == "flatrate") {
                selectShippingMethodAction(method);
                return true;
            }
            return false;
        },
    }

    return function (target) {
        return target.extend(mixin);
    };
});
