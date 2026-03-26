/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Coupon model.
 */
 define([
     'jquery',
    'Magento_Checkout/js/model/quote',
    'ko',
     'Magento_Ui/js/model/messageList',
     'Magento_Checkout/js/checkout-data',
     'mage/translate',
    'domReady!'
], function ($, quote, ko, messageList, checkoutData, $t) {
    'use strict';

    var appliedPoints = ko.observable(0),
        how_to_use = ko.observable(true),
        isApplied = ko.observable(true),
        isFullPoint = ko.observable(false);

    return {
        appliedPoints: appliedPoints,
        isApplied: isApplied,
        isFullPoint: isFullPoint,
        how_to_use: how_to_use,

        /**
         * @return {*}
         */
        getAppliedPoints: function () {
            return appliedPoints;
        },

                /**
         * @return {*}
         */
        getHowToUse: function () {
            return how_to_use;
        },

        /**
         * @return {Boolean}
         */
        getIsApplied: function () {
            return isApplied;
        },

        /**
         * @param {*} pointValue
         */
        setPoint: function (pointValue) {
            couponCode(pointValue);
        },

        /**
         * @param {Boolean} isAppliedValue
         */
        setIsApplied: function (isAppliedValue) {
            isApplied(isAppliedValue);
        },

        /**
         * @return {Boolean}
         */
        getIsFullPoint: function () {
            return isFullPoint;
        },

        /**
         * @param {Boolean} isFullPointValue
         */
        setIsFullPoint: function (isFullPointValue) {
            isFullPoint(isFullPointValue);
        },

        getPointsToBeEarned: function(used_point) {
            let usedPoints = (used_point * window.checkoutConfig.point_rate) / window.checkoutConfig.per_point;
            /*let tax = Math.round((quote.totals().subtotal_incl_tax - usedPoints) / (1 + window.checkoutConfig.tax_rate) * window.checkoutConfig.tax_rate);
            let discountedTotalAmountExcludeTax = quote.totals().subtotal_incl_tax - quote.totals().discount_amount - tax;
            let points_to_earn = (discountedTotalAmountExcludeTax * 100) * window.checkoutConfig.point_grant_rate;*/
            let discountAmount = quote.totals().discount_amount - usedPoints;
            let orderAmount = quote.totals().subtotal_incl_tax - discountAmount - usedPoints;
            let taxRate = (1 + window.checkoutConfig.tax_rate);
            let totalPointEarningAmount = orderAmount / taxRate;
            let points_to_earn = parseInt((totalPointEarningAmount * window.checkoutConfig.point_multiply_by) * window.checkoutConfig.point_grant_rate);

            /*console.log(quote.totals());
            console.log(orderAmount);
            console.log(points_to_earn);*/

            if(usedPoints) {
                if(!this.isValidAppliedPoint(usedPoints)) {
                    $("#points-form .action-cancel").click();
                    /*window.scrollTo(0, 0);*/
                    $("html, body").animate({ scrollTop: 0 }, 600);
                }
            }

            if(points_to_earn < 0){
                points_to_earn = 0;
            }

            setTimeout(function() {
                $('.message.message-error.error').hide();
            }, 5000);

            return points_to_earn;
        },

        getSubtotal: function () {
            if(!quote.totals()){
                return null;
            }
            return quote.totals().subtotal_incl_tax;
        },

        isValidAppliedPoint: function (point) {

            var usedPoints = (point * window.checkoutConfig.point_rate) / window.checkoutConfig.per_point;
            var subtotal = Math.round(this.getSubtotal());

            var selectedPaymentMethod = checkoutData.getSelectedPaymentMethod();
            if(!selectedPaymentMethod && $('.payment-method').hasClass('_active')) {
                selectedPaymentMethod = $('.payment-method._active input[name="payment[method]"]').val();
            }

            if(selectedPaymentMethod) {
                var minimumAmountErrorMessage = $t("Something went wrong while applying the points.");
                var minimumOrderAmount = 0;
                if(selectedPaymentMethod === 'worldpay_cc') {
                    minimumOrderAmount = window.checkoutConfig.minimum_oder_amount_world_pay_payment_method;
                    minimumAmountErrorMessage = $t("Please leave minimum payment amount of SGD %1 or pay the full amount in points.").replace('%1', Math.round(minimumOrderAmount));
                }

                if(selectedPaymentMethod === 'molpay_seamless') {
                    minimumOrderAmount = window.checkoutConfig.minimum_oder_amount_razer_payment_method;
                    minimumAmountErrorMessage = $t("Please leave minimum payment amount of MYR %1 or pay the full amount in points.").replace('%1', Math.round(minimumOrderAmount));
                }

                if(selectedPaymentMethod === 'omise_cc') {
                    minimumOrderAmount = window.checkoutConfig.minimum_oder_amount_omise_payment_method;
                    minimumAmountErrorMessage = $t("Please leave minimum payment amount of B %1 or pay the full amount in points.").replace('%1', Math.round(minimumOrderAmount));
                }

                if(selectedPaymentMethod === 'worldpay_apm') {
                    minimumOrderAmount = window.checkoutConfig.minimum_oder_amount_adyen_kakao_pay_payment_method;
                    minimumAmountErrorMessage = $t("Please leave minimum payment amount of W %1 or pay the full amount in points.").replace('%1', Math.round(minimumOrderAmount));
                }

                var remainingAmount = subtotal - usedPoints;
                if(minimumOrderAmount > 0 && remainingAmount < minimumOrderAmount) {
                    messageList.addErrorMessage({
                        'message': minimumAmountErrorMessage
                    });
                    /*$('.action-cancel').trigger('click');*/
                    return false;
                }
            }

            return true;
        }
    };
});
