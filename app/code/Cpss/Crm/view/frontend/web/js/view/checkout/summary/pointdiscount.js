define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Cpss_Crm/js/model/point'
    ],
    function (Component, quote, priceUtils, pointModel) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Cpss_Crm/checkout/summary/pointdiscount'
            },
            isDisplayedPointdiscount : function(){
                var appliedPoints = pointModel.appliedPoints();
                if(appliedPoints > 0) return true;
            },
            getPointDiscount : function(){
                var appliedPoints = pointModel.appliedPoints();
                return priceUtils.formatPrice(appliedPoints, quote.getPriceFormat());
            },
            getPointDiscountValue : function(){
                var appliedPoints = pointModel.appliedPoints();
                return appliedPoints.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            },
            isEnabled: function () {
                return window.checkoutConfig.enabled;
            }
        });
    }
 );
