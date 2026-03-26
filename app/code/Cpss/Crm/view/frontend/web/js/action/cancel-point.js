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
    'Cpss_Crm/js/model/payment/discount-messages',
    'mage/storage',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'mage/translate',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/recollect-shipping-rates',
    'Magento_Checkout/js/action/set-payment-information-extended',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/cart/totals-processor/default',
     'Cpss_Crm/js/model/point',
], function ($, quote, urlManager, errorProcessor, messageContainer, storage, getPaymentInformationAction, totals, $t,
  fullScreenLoader, recollectShippingRates,setPaymentInformationExtended,checkoutData,getTotalsAction,totalsProcessor, pointModel
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
    action =  function (isApplied, fromBack = false) {
        var quoteId = quote.getQuoteId(),
            url = action.getCancelPointUrl(1, quoteId),
            getEarnedPointsUrl = action.getEarnedPointsUrl(quoteId),
            message = $t('Your Applied points was successfully removed.');

            var pmethod = checkoutData.getSelectedPaymentMethod();
        messageContainer.clear();
        fullScreenLoader.startLoader();

        storage.post(
            url,
            false
        ).done(function () {
            $('.pointdiscount').hide();
            action.getEarnedPoints(getEarnedPointsUrl);
            var deferred = $.Deferred();

            var paymentData = {method: pmethod}
            if(!pmethod == null){
                setPaymentInformationExtended(messageContainer, paymentData, false);
            }

            // setPaymentInformationExtended(messageContainer, paymentData, false);
            totals.isLoading(true);
            if(quote.shippingAddress()){
                recollectShippingRates();
            }
            getPaymentInformationAction(deferred);
            //cartCache.set('totals',null);
            //totalsProcessor.estimateTotals(quote.shippingAddress());
            setTimeout(function(){totalsProcessor.estimateTotals(quote.shippingAddress()); }, 1000);
            $.when(deferred).done(function () {
                totalsProcessor.estimateTotals(quote.shippingAddress());
                isApplied(false);
                totals.isLoading(false);
                fullScreenLoader.stopLoader();
                //Allowing to tap into coupon-cancel process.
                callSuccessCallbacks();
            });

            pointModel.setIsFullPoint(false);
            quote.setPaymentMethod(null);

            $( ".payment-method" ).each(function( element ) {
                $(this).show();
                var elementId = '#'+$(this).next().find('input').attr('id');
                $(this).removeClass('_active');
                $(elementId).removeAttr("checked");
            });

            $('.fullpoint-parent').hide();
            $('#fullpoint').removeAttr("checked");

            $("input[name='how_to_use[]']").prop('checked', false);
            if(!fromBack){
                messageContainer.addSuccessMessage({
                    'message': message
                });
            }
        }).fail(function (response) {
            totals.isLoading(false);
            fullScreenLoader.stopLoader();
            errorProcessor.process(response, messageContainer);
        });
    };

    action.getCancelPointUrl = function (point, quoteId) {
        // calculate original points to earn
        /*let usedPoints = (used_point * window.checkoutConfig.point_rate) / window.checkoutConfig.per_point;
            let tax = Math.round((quote.totals().subtotal_incl_tax - usedPoints) / (1 + window.checkoutConfig.tax_rate) * window.checkoutConfig.tax_rate);
            let discountedTotalAmountExcludeTax = quote.totals().subtotal_incl_tax - quote.totals().discount_amount - tax;
            let points_to_earn = (discountedTotalAmountExcludeTax * 100) * window.checkoutConfig.point_grant_rate;*/
        let points_to_earn = parseInt((quote.totals().grand_total * window.checkoutConfig.point_multiply_by) * window.checkoutConfig.point_grant_rate);

        var params = urlManager.getCheckoutMethod() == 'guest' ? //eslint-disable-line eqeqeq
                {
                    quoteId: quoteId
                } : {},
            urls = {
                'customer': '/point/remove/' + JSON.stringify({ point: encodeURIComponent(point), points_to_earn: points_to_earn })
            };

        return urlManager.getUrl(urls, params);
    };

    action.getEarnedPointsUrl = function (quoteId) {
        var params = urlManager.getCheckoutMethod() == 'guest' ? //eslint-disable-line eqeqeq
                {
                    quoteId: quoteId
                } : {},
            urls = {
                'customer': '/point/getEarnedPoints/'
            };

        return urlManager.getUrl(urls, params);
    };

    action.getEarnedPoints = function (url) {
        // Commented out seems not used in the process
        // storage.get(
        //     url,
        //     ''
        // ).done(function (response) {
        //     if(response > 0){
        //         $('.earnedpoints').show();
        //         $('.earnedpoints .amount .earned-points').text(response);
        //     }else{
        //         $('.earnedpoints').hide();
        //     }
        // });
    }

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
