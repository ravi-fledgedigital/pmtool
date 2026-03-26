define([
    'jquery',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'jquery-ui-modules/widget',
    'mage/validation/validation',
    'mage/dataPost'
], function ($, mageTemplate, alert) {
    'use strict';
    return function(widget) {
        $.widget('mage.wishlist', $.mage.wishlist, {
            options: {
                dataAttribute: 'item-id',
                nameFormat: 'qty[{0}]',
                btnRemoveSelector: '[data-role=remove]',
                qtySelector: '[data-role=qty]',
                addToCartSelector: '[data-role=tocart]',
                addAllToCartSelector: '[data-role=all-tocart]',
                addSeletedToCartSelector: '[data-role=selected-tocart]',
                addCheckedToCartSelector: '[data-role=checked-tocart]',
                commentInputType: 'textarea',
                infoList: false
            },
            _create: function () {
                var _this = this;

                if (!this.options.infoList) {
                    this.element
                        .on('addToCart', function (event, context) {
                            var urlParams;

                            event.stopPropagation(event);
                            $(context).data('stop-processing', true);
                            urlParams = _this._getItemsToCartParams(
                                $(context).parents('[data-row=product-item]').find(_this.options.addToCartSelector)
                            );
                            $.mage.dataPost().postData(urlParams);

                            return false;
                        })
                        .on('click', this.options.btnRemoveSelector, $.proxy(function (event) {
                            event.preventDefault();
                            $.mage.dataPost().postData($(event.currentTarget).data('post-remove'));
                        }, this))
                        .on('click', this.options.addToCartSelector, $.proxy(this._beforeAddToCart, this))
                        .on('click', this.options.addAllToCartSelector, $.proxy(this._addAllWItemsToCart, this))
                        .on('click', this.options.addSeletedToCartSelector, $.proxy(this._addSelectedWItemsToCart, this))
                        .on('change', this.options.addCheckedToCartSelector, $.proxy(this._addCheckedToCartSelector, this))
                        .on('focusin focusout', this.options.commentInputType, $.proxy(this._focusComment, this));
                }

                // Setup validation for the form
                this.element.mage('validation', {
                    /** @inheritdoc */
                    errorPlacement: function (error, element) {
                        error.insertAfter(element.next());
                    }
                });
            },
            /**
             * Add selected wish list items to cart
             * @private
             */
            _addSelectedWItemsToCart: function () {
                var urlParams = this.options.addSelectedToCartUrl,
                    separator = urlParams.action.indexOf('?') >= 0 ? '&' : '?';
                var items = [];
                this.element.find(this.options.qtySelector).each(function (index, element) {
                    urlParams.action += separator + $(element).prop('name') + '=' + encodeURIComponent($(element).val());
                    separator = '&';
                });
                $('.account.wishlist-index-index .column.main .wishlist_checkbox .selected-product').filter(":checked").each(function () {
                    items.push(this.value);
                })
                urlParams.data['items'] = items.join(',');
                $.mage.dataPost().postData(urlParams);
            },
            _addCheckedToCartSelector: function () {
                $(this.options.addAllToCartSelector).show();
                $(this.options.addSeletedToCartSelector).hide();
                if($('.account.wishlist-index-index .column.main .wishlist_checkbox .selected-product').filter(":checked").length) {
                    $(this.options.addAllToCartSelector).hide();
                    $(this.options.addSeletedToCartSelector).show();
                }
            }
        });
        return $.mage.wishlist;
    }
});
