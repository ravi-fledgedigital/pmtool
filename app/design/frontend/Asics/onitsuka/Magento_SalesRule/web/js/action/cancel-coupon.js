/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer store credit(balance) application
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/error-processor',
    'Magento_SalesRule/js/model/payment/discount-messages',
    'mage/storage',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'mage/translate',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/recollect-shipping-rates'
], function ($, quote, urlManager, errorProcessor, messageContainer, storage, getPaymentInformationAction, totals, $t,
             fullScreenLoader, recollectShippingRates
) {
    'use strict';

    var successCallbacks = [],
        action,
        callSuccessCallbacks;

    /**
     * Execute callbacks when a coupon is successfully canceled.
     */
    callSuccessCallbacks = function () {
        successCallbacks.forEach(function (callback) {
            callback();
        });
    };

    /**
     * Cancel applied coupon.
     *
     * @param {Boolean} isApplied
     * @returns {Deferred}
     */
    action =  function (isApplied) {
        var quoteId = quote.getQuoteId(),
            url = urlManager.getCancelCouponUrl(quoteId),
            message = $t('Your coupon was successfully removed.');

        messageContainer.clear();
        fullScreenLoader.startLoader();

        return storage.delete(
            url,
            false
        ).done(function () {
            var deferred = $.Deferred();
            isApplied(false);
            totals.isLoading(true);
            if(quote.shippingAddress()){
                recollectShippingRates();
            }
            if (totals.extension_attributes && totals.extension_attributes.amrule_discount_breakdown) {
                totals.extension_attributes.amrule_discount_breakdown = null;
            }
            $('.form-discount-container .payment-option-inner .control input').removeAttr('disabled');
            fullScreenLoader.stopLoader();
            $('.total-rules').remove();
            getPaymentInformationAction(deferred);
            $.when(deferred).done(function () {
                isApplied(false);
                totals.isLoading(true);
                fullScreenLoader.stopLoader();
                //Allowing to tap into coupon-cancel process.
                callSuccessCallbacks();
                $('.form-discount-container .payment-option-inner .control input').removeAttr('disabled');
                $('.total-rules').remove();
                $('.loading-mask').hide();
            });
            messageContainer.addSuccessMessage({
                'message': message
            });
        }).fail(function (response) {
            totals.isLoading(true);
            fullScreenLoader.stopLoader();
            errorProcessor.process(response, messageContainer);
            $('.loading-mask').hide();
        });
    };

    /**
     * Callback for when the cancel-coupon process is finished.
     *
     * @param {Function} callback
     */
    action.registerSuccessCallback = function (callback) {
        successCallbacks.push(callback);
    };

    return action;
});
