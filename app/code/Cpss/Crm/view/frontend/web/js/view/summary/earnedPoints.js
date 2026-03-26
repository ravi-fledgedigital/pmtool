/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'Cpss_Crm/js/action/reset-applied-points'
], function (Component, quote, $t, resetAppliedPoints) {
    'use strict';

    resetAppliedPoints(true);

    return Component.extend({
        defaults: {
            template: 'Cpss_Crm/summary/earnedPoints'
        },
        /**
         * @return {*|Boolean}
         */
        isDisplayed: function () {
            var points_to_earn = window.checkoutConfig.points_to_earn;
            if(points_to_earn > 0) return true;
        },


        /**
         * Get discount title
         *
         * @returns {null|String}
         */
        getTitle: function () {
            return $t('Points to be earned');
        },


        /**
         * @return {*|String}
         */
        getValue : function(){
            var points_to_earn = window.checkoutConfig.points_to_earn;
            return points_to_earn;
        },


        /**
         * @return {*|String}
         */
         getCartValue : function(){
            // followed checkout confirmation acquired_points calculation
            // app/code/Cpss/Crm/view/frontend/web/js/view/payment/point.js:getPointsToBeEarned
            //let usedPoints = (used_point * window.checkoutConfig.point_rate) / window.checkoutConfig.per_point;
            /*let usedPoints = (used_point * window.checkoutConfig.point_rate) / window.checkoutConfig.per_point;
            let tax = Math.round((quote.totals().subtotal_incl_tax - usedPoints) / (1 + window.checkoutConfig.tax_rate) * window.checkoutConfig.tax_rate);
            let discountedTotalAmountExcludeTax = quote.totals().subtotal_incl_tax - quote.totals().discount_amount - tax;
            let points_to_earn = (discountedTotalAmountExcludeTax * 100) * window.checkoutConfig.point_grant_rate;*/
            let discountAmount = quote.totals().discount_amount;
            let orderAmount = quote.totals().subtotal_incl_tax - discountAmount;
            let taxRate = (1 + window.checkoutConfig.tax_rate);
            let totalPointEarningAmount = orderAmount / taxRate;
            let points_to_earn = parseInt((totalPointEarningAmount * window.checkoutConfig.point_multiply_by) * window.checkoutConfig.point_grant_rate);
            /*console.log(quote.totals());
            console.log(orderAmount);
            console.log(points_to_earn);*/

            if(points_to_earn < 0){
                points_to_earn = 0;
            }

            return points_to_earn.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    });
});
