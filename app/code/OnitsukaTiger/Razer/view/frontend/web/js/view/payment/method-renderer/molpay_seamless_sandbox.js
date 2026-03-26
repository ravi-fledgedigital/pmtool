/**
* Copyright 2015 Magento. All rights reserved.
* See COPYING.txt for license details.
*/
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'molpayseamlessdecosandbox',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order'

    ],
    function ( $, mssand, Component, additionalValidators, fullScreenLoader, quote, customer, placeOrderAction){

            var payloadmps;

            payloadmps = {
                cartId: quote.getQuoteId(),
                billingAddress: quote.billingAddress()
            };

            $(document).on('click', "input[name='payment_options']", function() {
                if( $("#mod_msg").length > 0 ) $("#mod_msg").remove();
            });

        return Component.extend({
            defaults: {
                template: 'OnitsukaTiger_Razer/payment/form'
            },

            getCode: function() {
                return 'molpay_seamless';
            },

            getData: function() {
                return {
                    'method': this.item.method
                };
            },

            getCurrentCartId: function(){
                return payloadmps.cartId;
            },

            getCurrentCustomerEmail: function(){
                var customerEmail = '';
                if(quote.guestEmail) customerEmail = quote.guestEmail;
                else customerEmail = window.checkoutConfig.customerData.email;
                return customerEmail;
            },

            getActiveChannels: function(){
                return window.checkoutConfig.payment.molpay_seamless.channels_payment;
            },

            placeOrder: function(){
                if (this.validate() &&
                    additionalValidators.validate() &&
                    this.isPlaceOrderActionAllowed() === true
                ) {
                    var paymentOptionsSelect = $("input[name='payment_options']:checked");
                    var myForm = $("#mpprod_seamless");

                    if( paymentOptionsSelect.length == 1 ){
                        if (myForm[0].checkValidity()) {
                            if($('.checkout-payment-method .payment-method .checkout-billing-address input[name="billing-address-same-as-shipping"]').length) {
                                if(!$('.checkout-payment-method .payment-method._active .checkout-billing-address input[name="billing-address-same-as-shipping"]').is(":checked")){
                                    $('#billingAddress').val(JSON.stringify(quote.billingAddress()));
                                }
                            }else{
                                if(!$('.checkout-payment-method .checkout-billing-address input[name="billing-address-same-as-shipping"]').is(":checked")){
                                    $('#billingAddress').val(JSON.stringify(quote.billingAddress()));
                                }
                            }
                            $('#addressExtension').val(JSON.stringify(quote.billingAddress()));
                            myForm.trigger("submit");
                        }
                    }
                    else{
                        if( $("#mod_msg").length == 0 ){
                            $("form#seamless > table")
                                .after("<div id='mod_msg' style='color:red;margin: 0px 5px 25px 5px;'>Please select payment options</div>");
                        }
                    }
                }
            }

        });

    }
);
