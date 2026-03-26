define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'mage/utils/wrapper'
], function ($, quote, wrapper) {
    'use strict';

    return function (selectBillingAddress) {
        return wrapper.wrap(selectBillingAddress, function (originalSelectBillingAddress, billingAddress) {
            if(quote.shippingMethod()) {
                if (quote.shippingMethod().carrier_code == 'freeshipping') {
                    billingAddress = quote.shippingAddress();
                }
            }
            if (billingAddress['extension_attributes'] === undefined) {
                billingAddress['extension_attributes'] = {};
            }

            if (billingAddress['customAttributes'] !== undefined) {
                $.each(billingAddress['customAttributes'], function (key, value) {
                    billingAddress['extension_attributes'][value.attribute_code] = value.value;
                });
            }

            originalSelectBillingAddress(billingAddress);
        });
    };
});
