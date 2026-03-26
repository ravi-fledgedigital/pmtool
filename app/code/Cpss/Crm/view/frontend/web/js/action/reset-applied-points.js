define([
    'jquery',
    'Cpss_Crm/js/model/point',
    'Cpss_Crm/js/action/cancel-point',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/action/select-payment-method'
], function (
    $,
    point,
    cancelPoint,
    checkoutData,
    selectPaymentMethodAction
) {
    'use strict';

    var self,
        appliedPoints = point.getAppliedPoints(),
        isApplied = point.getIsApplied();

    return function (isBack) {
        cancelPoint(isApplied, true);
        var clear = setInterval(function(){
            var loading = $('#checkout-loader').length;
            if(loading == 0 || isBack){
                let methods = '#checkout-payment-method-load input[type=radio]';
                $('#use_points').val('');
                point.setIsFullPoint(false);
                appliedPoints(0);
                $(methods).removeAttr('disabled');
                $(methods).parent().css({"pointer-events": "", "background": ""});

                setTimeout(function() {
                    let pointsBtn = '#points-form .payment-option-inner input[type=radio]',
                        pointsField = '#points-form .payment-option-inner #use_points';

                    $(pointsBtn).removeAttr('checked');
                    /*$(pointsField).attr('disabled', true);*/
                }, 200);

                clearInterval(clear);
            }
        }, 300);
    };
});
