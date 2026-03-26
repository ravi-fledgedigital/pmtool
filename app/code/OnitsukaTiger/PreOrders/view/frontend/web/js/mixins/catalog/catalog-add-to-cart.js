define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.catalogAddToCart', widget, {
            disableAddToCartButton: function (form) {
                var addToCartButton = $(form).find(this.options.addToCartButtonSelector);
                this.options.addToCartButtonTextDefault = addToCartButton.find('span').text();

                this._super(form);
            }
        });

        return $.mage.catalogAddToCart;
    }
});