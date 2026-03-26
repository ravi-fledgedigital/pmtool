define([
    'jquery',
    'mage/utils/wrapper',
    'OnitsukaTigerVn_Checkout/js/order/vat-information'
], function ($, wrapper, vatInformation) {
    'use strict';

    return function (placeOrderAction) {
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            vatInformation(paymentData);

            return originalAction(paymentData, messageContainer);
        });
    };
});
