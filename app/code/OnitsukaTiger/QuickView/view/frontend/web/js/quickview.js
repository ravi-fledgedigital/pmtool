
define(['jquery', 'mage/translate', 'magnificPopup'], function ($, $t) {
    "use strict";

    $.widget('OnitsukaTiger.QuickView', {
        options: {
            baseUrl: '/',
            popupTitle: $t('Quick View'),
            itemClass: '.products.grid .item.product-item, .products.list .item.product-item',
            btnLabel: $t('Quick View'),
            btnContainer: '.active-box',
            handlerClassName: 'btn_quick_view'
        },
        _create: function () {
            if (!$('body').hasClass('catalog-product-view')) {
                this._initialButtons(this.options);
                this._bindPopup(this.options);
            }
        },
        _initialButtons: function (config) {
            $(config.itemClass).not(".product-item-widget").each(function () {
                if (!$(this).find('.' + config.handlerClassName).length) {
                    var groupName = $(this).parent().attr('class').replace(' ', '-');
                    var productId = $(this).find('.price-final_price').data('product-id');
                    if (typeof productId !== 'undefined' && productId !== undefined && productId !== null) {
                        var url = config.baseUrl + 'quickview/catalog_product/view/id/' + productId;
                        var btnQuickView = '<div class="quick-view-btn-container">';
                        btnQuickView += '<a rel="' + groupName + '" class="' + config.handlerClassName + '" href="' + url + '"';
                        btnQuickView += ' title="' + config.popupTitle + '"';
                        btnQuickView += ' >';
                        btnQuickView += '<span>' + config.btnLabel + '</span></a>';
                        btnQuickView += '</div>';
                        $(this).find(config.btnContainer).prepend(btnQuickView);
                    }
                }
            });
        },
        _bindPopup: function (config) {
            var self = this;
            $('.' + config.handlerClassName).each(function () {
                $(this).magnificPopup({
                    type: 'ajax',
                    closeOnContentClick: false,
                    closeMarkup: '<button title="'+$t('Close (Esc)')+'" type="button" class="mfp-close"></button>',
                    tLoading:$t('Loading...'),
                    callbacks: {
                        ajaxContentAdded: function() {
                            // Ajax content is loaded and appended to DOM
                            $('.mfp-content .page-main .columns .column.main').removeClass('columns');
                            $('.mfp-content .page-main .columns').removeClass('columns');
                            $('.mfp-content').addClass('catalog-product-view');
                            $('.mfp-content').trigger('contentUpdated');
                            $('.label-increase').click(function(){
                                $(this).parent().find('input').val(parseInt($(this).parent().find('input').val()) + 1).trigger('change');
                            });
                            $('.label-decrease').click(function(){
                                $(this).parent().find('input').val(Math.max(1, parseInt($(this).parent().find('input').val()) - 1)).trigger('change');
                            });
                            $('.loadding-ajax').remove();
                            $('body').append('<div class="loadding-ajax"><div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>')
                            window.popupQuickview = this;
                            $('body .mfp-wrap .mfp-close').click(function() {
                                window.popupQuickview.closePopup()
                            });
                        }
                    }
                });
            });
        }
    });

    return $.OnitsukaTiger.QuickView;
});
