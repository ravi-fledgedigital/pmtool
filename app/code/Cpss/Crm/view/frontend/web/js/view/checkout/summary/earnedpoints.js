define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Cpss_Crm/js/model/point'
    ],
    function (Component, pointModel) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Cpss_Crm/checkout/summary/earnedpoints'
            },
            isDisplayedPointdiscount : function(){
                var appliedPoints = pointModel.appliedPoints();
                var points_to_earn = pointModel.getPointsToBeEarned(appliedPoints);
                if(points_to_earn >= 0) return true;
            },
            getValue : function(){
                var appliedPoints = pointModel.appliedPoints();
                var points_to_earn = pointModel.getPointsToBeEarned(appliedPoints);
                return points_to_earn.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            },
            isEnabled: function () {
                return window.checkoutConfig.enabled;
            },
        });
    }
 );
