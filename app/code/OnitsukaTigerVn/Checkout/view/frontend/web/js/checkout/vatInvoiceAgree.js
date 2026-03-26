define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'mage/url',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/validation'
    ],
    function (ko, $, Component,url,additionalValidators) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'OnitsukaTigerVn_Checkout/checkout/vatInvoiceCheckbox'
            },
            initObservable: function () {

                this._super()
                    .observe({
                        CheckVals: ko.observable(false)
                    });
                var checkVal=0;
                self = this;
                this.CheckVals.subscribe(function (newValue) {
                    var linkUrls  = url.build('agree/checkout/saveInQuote');
                    if(newValue) {
                        checkVal = 1;
                    }
                    else{
                        checkVal = 0;
                    }
                    $.ajax({
                        showLoader: true,
                        url: linkUrls,
                        data: {checkVal : checkVal},
                        type: "POST",
                        dataType: 'json'
                    }).done(function (data) {
                        console.log('success');
                    });
                });
                return this;
            },
            /**
             * Called before Place Order
             */
            validate: function () {
                if (this.CheckVals()) {
                    var form = $('#vat-invoice-form');
                    form.validation();
                    return form.validation('isValid');
                }

                return true; // skip if unchecked
            }
        });
    }
);
