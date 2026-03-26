/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer store credit(balance) application
 */
 define([
    'ko',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/error-processor',
    'Cpss_Crm/js/model/payment/discount-messages',
    'Cpss_Crm/js/model/point',
    'mage/storage',
    'mage/translate',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/recollect-shipping-rates',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/action/set-payment-information-extended',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/action/select-payment-method',
    'Cpss_Crm/js/action/reset-applied-points',
    'Magento_Checkout/js/model/cart/cache'
], function (ko, $, quote, urlManager, errorProcessor, messageContainer, pointModel, storage, $t, getPaymentInformationAction, totals, fullScreenLoader, recollectShippingRates, priceUtils, setPaymentInformationExtended, checkoutData, getTotalsAction, totalsProcessor, selectPaymentMethodAction, resetAppliedPoints, cartCache
) {
    'use strict';

    var dataModifiers = [],
        successCallbacks = [],
        failCallbacks = [],
        action;

    /**
     * Apply provided coupon.
     *
     * @param {String} point
     * @param {Boolean}isApplied
     * @returns {Deferred}
     */
    action = function (point, isApplied, how_to_use, points_to_earn) {
        var quoteId = quote.getQuoteId(),
            url = action.getApplyPointUrl(point, quoteId, how_to_use, points_to_earn),
            getEarnedPointsUrl = action.getEarnedPointsUrl(),
            message = $t('Your point was successfully applied.'),
            errMessage = $t('It was failed to get point info, please try again.'),
            data = {},
            headers = {};
        var pmethod = checkoutData.getSelectedPaymentMethod();

        //Allowing to modify coupon-apply request
        dataModifiers.forEach(function (modifier) {
            modifier(headers, data);
        });

        fullScreenLoader.startLoader();

        storage.post(
            url,
            ''
        ).done(function (response) {
            var deferred;
            if (response == "OK") {
                if (point > 0) {
                    $('.pointdiscount').show();
                    let usePoint = point;
                    $('.pointdiscount .amount .discount-amount').text(usePoint);
                }

                action.getEarnedPoints(getEarnedPointsUrl);

                deferred = $.Deferred();

                getTotalsAction([], deferred);

                var paymentData = { method: pmethod }
                if (!pmethod == null) {
                    setPaymentInformationExtended(messageContainer, paymentData, false);
                }

                isApplied(true);
                totals.isLoading(true);
                if(quote.shippingAddress()){
                    recollectShippingRates();
                }
                getPaymentInformationAction(deferred);
                cartCache.set('totals',null);
                totalsProcessor.estimateTotals(quote.shippingAddress());

                pointModel.appliedPoints(point);
                // $('#point-discount').html(point);

                $.when(deferred).done(function () {
                    cartCache.set('totals',null);
                    totalsProcessor.estimateTotals(quote.shippingAddress());
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                });

                let fullPoint = '#fullpoint',
                    fullpointParent = '.fullpoint-parent',
                    methods = '#checkout-payment-method-load input[type=radio]',
                    baseSubTotal = parseInt(quote.totals._latestValue.subtotal_incl_tax - quote.totals._latestValue.discount_amount),
                    baseTax = parseInt(quote.totals._latestValue.base_tax_amount),
                    baseSFInclTax = parseInt(quote.totals._latestValue.base_shipping_incl_tax),
                    baseTotalInclTax = baseSubTotal + baseTax,
                    isFullPoint = pointModel.getIsFullPoint()._latestValue,
                    currentMethod = checkoutData.getSelectedPaymentMethod(),
                    //gTotalInclTax = Math.round(Math.abs(quote.totals._latestValue.base_subtotal + quote.totals._latestValue.base_tax_amount));
                    gTotalInclTax = quote.totals._latestValue.base_grand_total,
                    appliedPointInAmount = (point * window.checkoutConfig.point_rate) / window.checkoutConfig.per_point;

                // temporary used subtotal
                if (appliedPointInAmount >= baseSubTotal && baseSFInclTax == 0 && gTotalInclTax == appliedPointInAmount) {
                    pointModel.setIsFullPoint(true);

                    $( ".payment-method" ).each(function( index, element ) {
                        $(this).hide();
                    });

                    $(fullpointParent).show();
                    $(fullPoint).trigger("click");
                    //$('#use_points').val('');
                    //$(fullPoint).trigger("click");
                    //$(methods).attr('disabled', true);
                    //$(methods).parent().css({ "pointer-events": "none", "background": "#f4f4f4" });
                } else {
                    $(fullpointParent).hide();
                    $(fullPoint).removeAttr("checked");
                    pointModel.setIsFullPoint(false);
                    quote.setPaymentMethod(null);
                    /*$(methods).removeAttr('disabled');
                    $(methods).parent().css({ "pointer-events": "", "background": "" });*/
                }

                // quote.getTotals().base_grand_total;
                // console.log(quote.totals._latestValue.base_grand_total);
                setTimeout(function () {
                    // console.log(quote.totals._latestValue.base_grand_total);
                    // paymentTotals().grandTotal(quote.totals._latestValue.base_grand_total);
                    let base_grand_total = quote.totals().subtotal_incl_tax + quote.totals().base_shipping_incl_tax - point;
                    let grandTotal = Math.max(quote.totals._latestValue.base_grand_total, 0);
                    // paymentTotals().grandTotal(priceUtils.formatPrice(grandTotal, quote.getPriceFormat()));
                }, 1500);
                messageContainer.addSuccessMessage({
                    'message': message
                });
                //Allowing to tap into apply-coupon process.
                successCallbacks.forEach(function (callback) {
                    callback(response);
                });
            } else {
                    resetAppliedPoints(true);
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                    errorProcessor.process(response, messageContainer);
                    messageContainer.addErrorMessage({
                        'message': errMessage
                    });
                    //Allowing to tap into apply-coupon process.
                    failCallbacks.forEach(function (callback) {
                        callback(response);
                    });
                    setTimeout(function () {
                        let grandTotal = Math.max(quote.totals._latestValue.base_grand_total, 0);
                        // paymentTotals().grandTotal(priceUtils.formatPrice(grandTotal, quote.getPriceFormat()));
                    }, 1000);
            }
        }).fail(function (response) {
            if (response != "OK") {
                resetAppliedPoints(true);
                fullScreenLoader.stopLoader();
                totals.isLoading(false);
                errorProcessor.process(response, messageContainer);
                messageContainer.addErrorMessage({
                    'message': errMessage
                });
                //Allowing to tap into apply-coupon process.
                failCallbacks.forEach(function (callback) {
                    callback(response);
                });
                setTimeout(function () {
                    let grandTotal = Math.max(quote.totals._latestValue.base_grand_total, 0)
                    // paymentTotals().grandTotal(priceUtils.formatPrice(grandTotal, quote.getPriceFormat()));
                }, 1000);
            }
        });
        var deferred = $.Deferred();
        getTotalsAction([], deferred);
    };

    action.getApplyPointUrl = function (point, quoteId, how_to_use, points_to_earn) {
        var params = urlManager.getCheckoutMethod() == 'guest' ? //eslint-disable-line eqeqeq
            {
                quoteId: quoteId
            } : {},
            urls = {
                'customer': '/point/set/' + JSON.stringify({ point: encodeURIComponent(point), how_to_use: how_to_use, points_to_earn: points_to_earn })
            };

        return urlManager.getUrl(urls, params);
    };

    action.getEarnedPointsUrl = function () {
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
        //     if (response > 0) {
        //         $('.earnedpoints').show();
        //         $('.earnedpoints .amount .earned-points').text(response);
        //     } else {
        //         $('.earnedpoints').hide();
        //     }
        // });
    }

    /**
     * Modifying data to be sent.
     *
     * @param {Function} modifier
     */
    action.registerDataModifier = function (modifier) {
        dataModifiers.push(modifier);
    };

    /**
     * When successfully added a coupon.
     *
     * @param {Function} callback
     */
    action.registerSuccessCallback = function (callback) {
        successCallbacks.push(callback);
    };

    /**
     * When failed to add a coupon.
     *
     * @param {Function} callback
     */
    action.registerFailCallback = function (callback) {
        failCallbacks.push(callback);
    };


    return action;
});
