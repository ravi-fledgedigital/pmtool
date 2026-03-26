define([
    'Magento_Checkout/js/view/payment/default',
    'mage/url',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    Component,
    urlBuilder,
    $,
    quote,
    fullScreenLoader
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'OnitsukaTiger_PortOne/payment/portone-transfer',
            redirectAfterPlaceOrder: false
        },

        afterPlaceOrder: function () {
            fullScreenLoader.startLoader();

            const config = window.checkoutConfig.payment.portonetransfer;
            const totalAmount = quote.totals().grand_total;
            const currencyCode = quote.totals()?.quote_currency_code || 'KRW';
            const portOneCurrency = 'CURRENCY_' + currencyCode;
            const paymentId = 'payment_' + Date.now();

            $.ajax({
                type: 'POST',
                url: urlBuilder.build('portone/order/getOrderInfo'),
                dataType: 'json',
                async: false
            }).done(orderInfo => {
                if (orderInfo.success) {
                    fullScreenLoader.stopLoader();
                    const orderIncrementId = orderInfo.increment_id;
                    const orderId = orderInfo.order_id;
                    const paymentId = orderInfo.paymentId;

                    let paymentParams = {
                        storeId: config.storeId,
                        channelKey: config.channelKey,
                        paymentId: paymentId,
                        orderName: orderIncrementId,
                        totalAmount: totalAmount,
                        currency: portOneCurrency,
                        payMethod: config.payMethod
                    };

                    // Add redirectUrl only for mobile devices
                    if (window.innerWidth < 769) {
                        paymentParams.redirectUrl = BASE_URL + 'checkout/onepage/success';
                    }

                    PortOne.requestPayment(paymentParams)
                        .then(response => {
                        fullScreenLoader.startLoader();
                        const isSuccess = !response.code;

                        const additionalData = {
                            portone_payment_order_id: orderId,
                            portone_payment_status: isSuccess ? 'success' : 'fail',
                            portone_payment_id: response.paymentId,
                            portone_transaction_type: response.transactionType,
                            portone_tx_id: response.txId,
                            portone_full_response: JSON.stringify(response)
                        };

                        if (!isSuccess) {
                            additionalData.portone_failure_reason = response.message || 'Unknown error';
                        }

                        return $.ajax({
                            url: urlBuilder.build('portone/order/savePaymentInfo'),
                            type: 'POST',
                            data: JSON.stringify({ response: additionalData }),
                            contentType: 'application/json',
                            complete: function () {
                                fullScreenLoader.stopLoader();
                                const redirectUrl = isSuccess
                                    ? urlBuilder.build('checkout/onepage/success')
                                    : urlBuilder.build('checkout/onepage/failure');

                                window.location.href = redirectUrl;
                            }
                        });
                    }).catch(() => {
                        window.location.href = urlBuilder.build('checkout/onepage/failure');
                    }).finally(() => {
                        fullScreenLoader.stopLoader();
                    });
                } else {
                    this._handleError('Failed to get order info.');
                }
            }).fail(() => {
                this._handleError('Ajax error while fetching order info.');
            });
        },

        _handleError: function (message) {
            fullScreenLoader.stopLoader();
            this.messageContainer.addErrorMessage({ message });
            console.error(message);
            window.location.href = urlBuilder.build('checkout/onepage/failure');
        }
    });
});
