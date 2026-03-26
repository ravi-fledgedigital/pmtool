/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Cpss_Crm/js/action/cancel-point',
    'Cpss_Crm/js/model/point',
    'Cpss_Crm/js/action/set-point',
    'Cpss_Crm/js/model/payment/discount-messages',
    'mage/translate',
    'Cpss_Crm/js/action/reset-applied-points',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/checkout-data'

], function ($, ko, Component, quote, cancelPoint, point, setPoint, messageContainer, $t, resetAppliedPoints, messageList, checkoutData) {
    'use strict';

    var totals = quote.getTotals(),
        appliedPoints = point.getAppliedPoints(),
        howtouse = point.getHowToUse(),
        isApplied = point.getIsApplied();

    var points_applied = window.checkoutConfig.appliedPoints;
    var how_to_use = window.checkoutConfig.howtouse;

    resetAppliedPoints(true);
    if (points_applied) {

        appliedPoints(points_applied);
        howtouse(how_to_use);
    }

    isApplied(appliedPoints() != null);


    return Component.extend({
        defaults: {
            template: 'OnitsukaTigerCpss_Crm/payment/point'
        },
        appliedPoints: appliedPoints,
        how_to_use: howtouse,

        /**
         * Applied flag
         */
        isApplied: isApplied,

        /**
         * Coupon code application procedure
         */
        applyPoints: function () {
            var currentPoints = window.checkoutConfig.currentPoints;
            var how_to_use = "";
            if (currentPoints == 0) {
                return;
            }

            if ($("input[name='how_to_use[]']:checked").val() == "use_all") {
                $('#use_points').val('');
                how_to_use = "use_all";
                /*currentPoints = currentPoints * window.checkoutConfig.per_point;*/
                let usedPoints = (currentPoints * window.checkoutConfig.point_rate) / window.checkoutConfig.per_point;

                if (usedPoints >= totals()['subtotal_incl_tax']) {
                    currentPoints = Math.round((totals()['subtotal_incl_tax'] - totals()['discount_amount']) * window.checkoutConfig.per_point);
                }
                if(this.isValidAppliedPoint(currentPoints)) {
                    var points_to_earn = point.getPointsToBeEarned(currentPoints);
                    setPoint(currentPoints, isApplied, how_to_use, points_to_earn);
                    $('#use_points').val(currentPoints);
                    /*$('#use_points').addClass('disabled');*/
                } else {
                    /*window.scrollTo(0, 0);*/
                    $("html, body").animate({ scrollTop: 0 }, 600);
                }
            } else {
                how_to_use = "use_points";
                var points_applied = $('#use_points').val();

                if (this.pointIsLowerThanCurrentPoint(points_applied, currentPoints) &&
                    parseInt(points_applied) >= 0 &&
                    this.isValidAppliedPoint(points_applied) &&
                    this.isAppliedPointsWholeNumber(points_applied)
                ) {
                    var points_to_earn = point.getPointsToBeEarned(points_applied);
                    // if(points_applied > )
                    setPoint(points_applied, isApplied, how_to_use, points_to_earn);

                    /*$('#use_points').addClass('disabled');*/
                } else {
                    /*window.scrollTo(0, 0);*/
                    $("html, body").animate({ scrollTop: 0 }, 600);
                }
            }

            setTimeout(function() {
                $('.message.message-error.error').hide();
            }, 8000);

            /*if(quote.getUsedPoint()) {
                $('#use_points').val(quote.getUsedPoint());
            }*/
        },

        /**
         * Cancel using coupon
         */
        cancelPoints: function () {
            //if (this.validate()) {
            appliedPoints('');
            cancelPoint(isApplied);
            /*$('#use_points').val('').removeClass('disabled');*/
            $('.fullpoint-parent').hide();
            $('#fullpoint').removeAttr("checked");
            resetAppliedPoints(true);
            //}
        },

        /**
         * Coupon form validation
         *
         * @returns {Boolean}
         */
        validate: function () {
            var form = '#discount-form';

            return $(form).validation() && $(form).validation('isValid');
        },

        hasPoint: function () {
            var currentPoints = window.checkoutConfig.currentPoints;
            if(currentPoints > 0) return true;
        },

        getCurrentPoints: function () {
            var currentPoints = window.checkoutConfig.currentPoints;
            return currentPoints;
        },

        getSubtotal: function () {
            if(!quote.totals()){
                return null;
            }
            return quote.totals().subtotal_incl_tax;
        },

        getDiscount: function () {
            if(!quote.totals()){
                return null;
            }
            return quote.totals().discount_amount;
        },

        isAppliedPointsWholeNumber: function (point) {
            if(point != parseInt(point)){
                messageList.addErrorMessage({
                    'message': $t("Please use whole numbers.")
                });
                return false;
            }

            return true;
        },
        isValidAppliedPoint: function (point) {

            var currentPoints = window.checkoutConfig.currentPoints;
            if(parseInt(point) > parseInt(currentPoints)){
                messageList.addErrorMessage({
                    'message': $t("Please use points 0 to %1.").replace('%1', Math.round(currentPoints))
                });
                return false;
            }

            var minPoint = parseInt(window.checkoutConfig.minimumPoints);
            if(minPoint > point) {
                messageList.addErrorMessage({
                    'message': $t("Please use minimum %1 points.").replace('%1', Math.round(minPoint))
                });
                return false;
            }

            var pointMultiplyBy = parseInt(window.checkoutConfig.pointMultiplyBy);
            if (parseInt(point) % pointMultiplyBy !== 0) {
                messageList.addErrorMessage({
                    'message': $t("Please input values in multiples of %1 points.").replace('%1', Math.round(pointMultiplyBy))
                });
                return false;
            }

            var usedPoints = (point * window.checkoutConfig.point_rate) / window.checkoutConfig.per_point;
            var subtotal = Math.round(this.getSubtotal() - this.getDiscount());

            if(usedPoints > subtotal){
                messageList.addErrorMessage({
                    'message': $t("You cannot redeem points more than subtotal amount.")
                });
                return false;
            }

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
                    return false;
                }
            }

            return true;

            /*var maxPoint = 0;
            var subtotal = Math.round(this.getSubtotal());
            var maxOwnedPoint = parseInt(this.getCurrentPoints())
            if (maxOwnedPoint >= subtotal) {
                maxPoint = subtotal;
            } else if (maxOwnedPoint > subtotal) {
                maxPoint = maxOwnedPoint;
            }*/

            /*if(subtotal >= point){
                return true;
            }else {
                messageContainer.addErrorMessage({
                    'message': $t("Please use points 0 to %1.").replace('%1', Math.round(maxPoint))
                });
                return false;
            }*/
        },
        pointIsLowerThanCurrentPoint: function (point, currentPoints ) {
            if(parseInt(point) <= parseInt(currentPoints)){
                return true;
            }
            else {
                messageList.addErrorMessage({
                    'message': $t("Please use points 0 to %1.").replace('%1', Math.round(currentPoints))
                });
                return false;
            }
        },
        isEnabled: function () {
            return window.checkoutConfig.enabled;
        },

        isEnabledPointCheckoutPage: function () {
            return window.checkoutConfig.enablePointCheckoutPage;
        },
        selectUse: function () {
            /*$('#use_points').prop('disabled', false).removeClass('disabled');*/
            if($("input[name='how_to_use[]']:checked").val() == "use_all")
            {
                /*$('#use_points').addClass('disabled').val('');*/
            }
            else{
                /*$('#use_points').removeClass('disabled');*/
            }
        },

        formatPointDisplay: function(value){
            return value.toString().replace(/,/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    });
});
