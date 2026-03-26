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
    var mixin = {

        applyPoints: function () {
            var currentPoints = window.checkoutConfig.currentPoints;
            var how_to_use = $("input[name='how_to_use[]']:checked").val();
            var minimumPoints = window.checkoutConfig.minimumPoints;

            if (currentPoints == 0) return;

            if (how_to_use == "use_all") {
                $('#use_points').val('');

                if (currentPoints >= totals()['subtotal_incl_tax']) {
                    currentPoints = Math.round(totals()['subtotal_incl_tax']);
                }

                var points_to_earn = point.getPointsToBeEarned(currentPoints);
                setPoint(currentPoints, isApplied, how_to_use, points_to_earn);
            } else if (how_to_use == "use_points") {
                var points_applied = $('#use_points').val();

                if (this.pointIsLowerThanCurrentPoint(points_applied, currentPoints, minimumPoints) &&
                    parseInt(points_applied) >= 0 &&
                    this.isValidAppliedPoint(points_applied, minimumPoints) &&
                    this.checkMinimumAppliedPoint(points_applied, minimumPoints) &&
                    this.isAppliedPointsWholeNumber(points_applied)
                ) {
                    var points_to_earn = point.getPointsToBeEarned(points_applied);
                    // if(points_applied > )
                    setPoint(points_applied, isApplied, how_to_use, points_to_earn);
                }
            }
        },
        isValidAppliedPoint: function (point, minimumpoints) {
            var maxPoint = 0;
            var maxOwnedPoint = parseInt(this.getCurrentPoints())
            var baseSFInclTax = quote.totals._latestValue.base_shipping_incl_tax;
            var subTotalWithOutShippingFee = quote.totals._latestValue.base_grand_total-baseSFInclTax;
            var subtotal = subTotalWithOutShippingFee;
            if (maxOwnedPoint >= subtotal) {
                maxPoint = subtotal;
            } else if (maxOwnedPoint > subtotal) {
                maxPoint = maxOwnedPoint;
            }

            if (subtotal >= point) {
                return true;
            } else {
                messageContainer.addErrorMessage({
                    'message': $t("Please use points %1 to %2.").replace('%1', Math.round(minimumpoints)).replace('%2', Math.round(maxPoint))
                });
                return false;
            }
        },
        checkMinimumAppliedPoint: function (point, minimumPoints) {
            if (parseInt(point) >= parseInt(minimumPoints)) {
                return true;
            } else {
                messageContainer.addErrorMessage({
                    'message': $t("Please use limit points minimum %1.").replace('%1', minimumPoints)
                });
                return false;
            }
        }
    };
    return function (target) {
        return target.extend(mixin);
    };
});
