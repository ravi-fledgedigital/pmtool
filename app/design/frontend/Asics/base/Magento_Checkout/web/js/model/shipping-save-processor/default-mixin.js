define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/model/shipping-save-processor/payload-extender',
    'mage/utils/wrapper'
], function (
    ko,
    quote,
    resourceUrlManager,
    storage,
    paymentService,
    methodConverter,
    errorProcessor,
    fullScreenLoader,
    selectBillingAddressAction,
    payloadExtender,
    wrapper
) {
    'use strict';
    return function (actionsaveShippingInformation) {
        actionsaveShippingInformation.saveShippingInformation = wrapper.wrapSuper(actionsaveShippingInformation.saveShippingInformation, function () {
            selectBillingAddressAction(quote.shippingAddress());
            this._super();
        });

        return actionsaveShippingInformation;
    };
});
