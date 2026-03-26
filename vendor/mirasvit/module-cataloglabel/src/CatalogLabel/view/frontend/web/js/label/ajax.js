define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mst.cataloglabel', {
        options: {
            requestUrl: ''
        },

        loaded: [],

        _create: function () {
            this._bind();
        },

        _bind: function () {
            $('.product-info-main, .product-item-info').on('click', function (e) {
                if ($(e.target).hasClass('swatch-option') || $(e.target).hasClass('swatch-select')) {
                    const params    = {};
                    const productId = $('input[name="product"]', e.currentTarget).val();
                    const viewType  = $(e.currentTarget).hasClass('product-info-main') ? 'view' : 'list';

                    params[productId] = {
                        swatches: {},
                        placeholders: [],
                        type: viewType
                    };

                    $('.swatch-attribute', e.currentTarget).each(function (idx, swatch) {
                        params[productId]['swatches'][$(swatch).attr('data-attribute-code')] = $(swatch).attr('data-option-selected');
                    });

                    $('.cataloglabel-placeholder', e.currentTarget).each(function (idx, placeholder) {
                        params[productId]['placeholders'].push($(placeholder).attr('data-placeholder'));
                    });

                    if (viewType == 'view') {
                        $('.cataloglabel-placeholder', $('.main .product.media, .main .product.info')).each(function (idx, placeholder) {
                            params[productId]['placeholders'].push($(placeholder).attr('data-placeholder')); //product view page
                        });
                    }

                    this.requestUpdate(params, e.currentTarget);
                }
            }.bind(this))
        },

        requestUpdate: function (params, target) {
            $.ajax(this.options.requestUrl, {
                method: 'POST',
                global: false,
                data: {'productData': params}
            }).done(function (result) {
                for (let pId in result.blocks) {
                    for (let code in result.blocks[pId]) {
                        $('.placeholder-' + code, target).html($(result.blocks[pId][code]).html());
                        $('.placeholder-' + code, $('.main .product.media, .main .product.info')).html($(result.blocks[pId][code]).html()); // product view page
                    }
                }
            })
        }

    });

    return $.mst.cataloglabel;
});
