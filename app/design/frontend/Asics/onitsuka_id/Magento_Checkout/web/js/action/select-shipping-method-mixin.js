define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (selectShippingMethod) {
        return wrapper.wrap(selectShippingMethod, function (originalAction, shippingMethod) {
            var stepCode = window.location.hash;
            if (shippingMethod && shippingMethod['carrier_code'] == 'freeshipping') {
                if (stepCode == '#payment') {
                    $('body').addClass('pickup-store-payment');
                }
            }
            return originalAction(shippingMethod);
        });
    };
});
