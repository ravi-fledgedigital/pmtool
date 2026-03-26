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
    'Cpss_Crm/js/action/reset-applied-points'

], function ($, ko, Component, quote, cancelPoint, point, setPoint, messageContainer, $t, resetAppliedPoints) {
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

        $("input[name='how_to_use[]']").each(function() {

            if($(this).val() == how_to_use){
                $(this).attr('checked',true);
            }
        });
    }
    /*$('#use_points').attr('disabled', true);*/

    isApplied(appliedPoints() != null);


    return Component.extend({
        defaults: {
            template: 'Cpss_Crm/payment/point'
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
            var how_to_use = $("input[name='how_to_use[]']:checked").val();

            if (currentPoints == 0) return;

            if (how_to_use == "use_all") {
                $('#use_points').val('');

                if (currentPoints >= totals()['subtotal_incl_tax']) {
                    currentPoints = Math.round(totals()['subtotal_incl_tax']);
                }

                var points_to_earn = point.getPointsToBeEarned(currentPoints);
                setPoint(currentPoints, isApplied, how_to_use, points_to_earn);
            } else if(how_to_use == "use_points") {
                var points_applied = $('#use_points').val();

                if (this.pointIsLowerThanCurrentPoint(points_applied, currentPoints) &&
                    parseInt(points_applied) >= 0 &&
                    this.isValidAppliedPoint(points_applied) &&
                    this.isAppliedPointsWholeNumber(points_applied)
                ) {
                    var points_to_earn = point.getPointsToBeEarned(points_applied);
                    // if(points_applied > )
                    setPoint(points_applied, isApplied, how_to_use, points_to_earn);
                }
            }
        },

        /**
         * Cancel using coupon
         */
        cancelPoints: function () {
            //if (this.validate()) {
                appliedPoints('');
                cancelPoint(isApplied);
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
        isAppliedPointsWholeNumber: function (point) {
            if(point != parseInt(point)){
                messageContainer.addErrorMessage({
                    'message': $t("Please use whole numbers.")
                });
                return false;
            }

            return true;
        },
        isValidAppliedPoint: function (point) {
            var maxPoint = 0;
            var subtotal = Math.round(this.getSubtotal());
            var maxOwnedPoint = parseInt(this.getCurrentPoints())
            if (maxOwnedPoint >= subtotal) {
                maxPoint = subtotal;
            } else if (maxOwnedPoint > subtotal) {
                maxPoint = maxOwnedPoint;
            }

            if(subtotal >= point){
                return true;
            }else {
                messageContainer.addErrorMessage({
                    'message': $t("Please use points 0 to %1.").replace('%1', Math.round(maxPoint))
                });
                return false;
            }
        },
        pointIsLowerThanCurrentPoint: function (point, currentPoints ) {
            if(parseInt(point) <= parseInt(currentPoints)){
                return true;
            }
            else {
                messageContainer.addErrorMessage({
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

            if(how_to_use == 'use_all'){
                /*$('#use_points').attr('disabled',true);*/
            }
            if($("input[name='how_to_use[]']:checked").val() == "use_points")
            {
                /*$('#use_points').removeAttr('disabled');*/
            }
            else{
                /*$('#use_points').attr('disabled',true);*/
            }
        },
        formatPointDisplay: function(value){
            return value.toString().replace(/,/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    });
});
