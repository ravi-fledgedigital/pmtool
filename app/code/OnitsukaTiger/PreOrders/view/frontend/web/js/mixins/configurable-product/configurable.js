define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.configurable', widget, {
            _initializeOptions: function () {
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
            _reloadPrice: function () {
                var self = this,
                    productId = self.simpleProduct,
                    config = self.options.spConfig.onitsukatiger_preorders_product,
                    stockMessage = '',
                    buttonTitle = '';

                if (productId) {
                    var productData = config[productId];
                }

                if (productData) {
                    stockMessage = productData['stockMessage'];
                    buttonTitle = productData['buttonTitle'];
                }

                if (!stockMessage) {
                    stockMessage = self.originalStockMessage;
                }

                if (!buttonTitle) {
                    buttonTitle = self.originalButtonTitle;
                }

                this.stockMessageBlock.html(stockMessage);
                this.buttonLabelBlock.html(buttonTitle);

                return this._super();
            }
        });

        return $.mage.SwatchRenderer;
    }
});