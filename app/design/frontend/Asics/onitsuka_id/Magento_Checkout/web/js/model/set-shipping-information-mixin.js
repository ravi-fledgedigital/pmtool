define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/pick_up_store'
], function ($, wrapper, quote, pickUpStore) {
    'use strict';

    return function (setShippingInformationAction) {

        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var shippingAddress = quote.shippingAddress();

            if (shippingAddress['extension_attributes'] === undefined) {
                shippingAddress['extension_attributes'] = {};
            }

            if (shippingAddress['customAttributes'] !== undefined) {
                $.each(shippingAddress['customAttributes'], function (key, value) {
                    shippingAddress['extension_attributes'][value.attribute_code] = value.value;
                });
            }

            if (pickUpStore.getStoreId() != null) {
                var location = checkoutConfig.pickup_config.pickItems[pickUpStore.getStoreId()];
                shippingAddress['extension_attributes']['store_name'] = location.storeName;
                shippingAddress['extension_attributes']['store_code'] = location.storeCode;
                shippingAddress['extension_attributes']['store_phone'] = location.storePhone;
                shippingAddress['extension_attributes']['store_email'] = location.storeEmail;
                shippingAddress['extension_attributes']['latitude'] = location.latitude;
                shippingAddress['extension_attributes']['longitude'] = location.longitude;
            }

            return originalAction();
        });
    };
});