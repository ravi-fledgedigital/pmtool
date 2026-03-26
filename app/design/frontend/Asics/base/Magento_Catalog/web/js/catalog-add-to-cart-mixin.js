define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';
    return function(widget) {
        $.widget('mage.catalogAddToCart', $.mage.catalogAddToCart, {
            /** @inheritdoc */
            _create: function () {
                this._super();
                this.initMinicartOpen($("[data-block='minicart']"));

            },
            /**
             * Handler for the form 'submit' event
             *
             * @param {jQuery} form
             */
            submitForm: function (form) {
                this.ajaxSubmit(form);
                $('.mfp-close-btn-in .mfp-close').trigger('click');
                $('body').removeClass('ajax-loading').addClass('ajax-loading');
            },
            initMinicartOpen: function (minicart) {
                minicart.on('contentLoading', function () {
                    minicart.on('contentUpdated', function () {
                        minicart.find('[data-role="dropdownDialog"]').dropdownDialog("open");
                    });
                });
            }
        });
        return $.mage.catalogAddToCart;
    }
});
