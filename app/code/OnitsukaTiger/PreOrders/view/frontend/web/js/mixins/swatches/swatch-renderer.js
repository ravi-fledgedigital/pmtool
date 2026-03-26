define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.SwatchRenderer', widget, {
            _init: function () {
                this._super();

                var stockMessageSelector = '',
                    buttonLabelSelector = '.actions button.primary span',
                    productBlock = this.element.parents(this.options.selectorProduct);

                if (productBlock.hasClass('product-info-main')) {
                    stockMessageSelector = '.product-info-price .product-info-stock-sku .stock span';
                } else {
                    stockMessageSelector = '.preorder_note'
                }

                this.stockMessageBlock = productBlock.find(stockMessageSelector).last();
                this.buttonLabelBlock = productBlock.find(buttonLabelSelector);
                this.originalStockMessage = this.stockMessageBlock.html();
                this.originalButtonTitle = this.buttonLabelBlock.html();
            },
            _Rebuild: function () {
                var $widget = this,
                    productId = $widget.getProduct(),
                    productData = $widget.options.jsonConfig.onitsukatiger_preorders_product[productId],
                    stockMessage = '',
                    buttonTitle = '';

                if (productData) {
                    stockMessage = productData['stockMessage'];
                    buttonTitle = productData['buttonTitle'];
                }

                if (!stockMessage) {
                    stockMessage = $widget.originalStockMessage;
                }

                if (!buttonTitle) {
                    buttonTitle = $widget.originalButtonTitle;
                }

                this.stockMessageBlock.html(stockMessage);
                this.buttonLabelBlock.html(buttonTitle);
                return this._super();
            }
        });

        return $.mage.SwatchRenderer;
    }
});