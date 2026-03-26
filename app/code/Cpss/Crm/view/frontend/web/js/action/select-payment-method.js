define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'jquery',
        'Magento_Checkout/js/action/get-totals',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
    ],
    function ($, quote, fullScreenLoader, jQuery, getTotalsAction, $t, messageList) {
        'use strict';
        return function (paymentMethod) {
            quote.paymentMethod(paymentMethod);

            fullScreenLoader.startLoader();

            var selectedPaymentMethod = (paymentMethod && paymentMethod.method) ? paymentMethod.method : '',
                minimumOrderAmount = 0,
                minimumAmountErrorMessage = '';

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

            var subtotal = quote.totals().subtotal_incl_tax,
                usedPoints = $('#use_points').val();

            if(usedPoints && usedPoints > 0) {
                usedPoints = usedPoints / 100;
            }

            var remainingAmount = subtotal - usedPoints;

            if(minimumOrderAmount > 0 && remainingAmount < minimumOrderAmount) {
                messageList.addErrorMessage({
                    'message': minimumAmountErrorMessage
                });

                $('.action-cancel').trigger('click');
                var deferred = $.Deferred();
                getTotalsAction([], deferred);
            }

            fullScreenLoader.stopLoader();

        }
    }
);
