define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url'
    ],
    function (ko, $, Component,additionalValidators, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'OnitsukaTigerVn_Checkout/checkout/termsAndConditionAgree',
                termsUrl: url.build('terms-and-conditions'),
                privacyUrl: url.build('privacy-policy')
            },
            initObservable: function () {

                this._super()
                    .observe({
                        termsChecked: ko.observable(false)
                    });
                var termscheckVal=0;
                self = this;
                this.termsChecked.subscribe(function (newValue) {
                    var linkUrls  = url.build('agree/checkout/saveInQuote');
                    if(newValue) {
                        termscheckVal = 1;
                        $('.mage-error.terms-and-conditions-error-message').hide();
                    }
                    else{
                        termscheckVal = 0;
                        $('.mage-error.terms-and-conditions-error-message').show();
                        $('html, body').animate({
                            scrollTop: $('.mage-error.terms-and-conditions-error-message').offset().top - 100
                        }, 500);
                    }
                    $.ajax({
                        showLoader: true,
                        url: linkUrls,
                        data: {termscheckVal : termscheckVal},
                        type: "POST",
                        dataType: 'json'
                    }).done(function (data) {
                        console.log('success');
                    });
                });
                return this;
            }
        });
    }
);
