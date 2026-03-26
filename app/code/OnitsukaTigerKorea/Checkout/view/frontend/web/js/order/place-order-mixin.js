define([
    'jquery',
    'mage/utils/wrapper',
    'OnitsukaTigerKorea_Checkout/js/order/giftpackging-assigner'
], function ($, wrapper, giftPackgingAssigner) {
    'use strict';

    return function (placeOrderAction) {
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            giftPackgingAssigner(paymentData);

            return originalAction(paymentData, messageContainer);
        });
    };
});