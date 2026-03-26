/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/view/messages',
    '../../model/payment/discount-messages',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/action/recollect-shipping-rates',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/cart/totals-processor/default'
], function ($, Component, messageContainer, getPaymentInformationAction, recollectShippingRates, quote, totalsProcessor) {
    'use strict';
    try {
        setTimeout(function(){
            recollectShippingRates();
            totalsProcessor.estimateTotals(quote.shippingAddress());
        }, 3000);
        totalsProcessor.estimateTotals(quote.shippingAddress());

        var deferred = $.Deferred();
        getPaymentInformationAction(deferred);
    } catch (error) {
    }

    return Component.extend({
        /** @inheritdoc */
        initialize: function (config) {
            return this._super(config, messageContainer);
        },
        isEnabled: function () {

            return window.checkoutConfig.enabled;
        }
    });
});
