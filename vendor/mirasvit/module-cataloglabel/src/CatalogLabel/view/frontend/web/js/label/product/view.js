define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        if ($('.catalog-product-view').length) {
            $('[data-gallery-role=gallery-placeholder]').on('gallery:loaded', function () {
                $('.product.media .cataloglabel.cataloglabel-placeholder').each(function () {
                    $(this).appendTo('.fotorama__wrap').insertAfter('.fotorama__spinner');
                });
            });
        }
    };
});
