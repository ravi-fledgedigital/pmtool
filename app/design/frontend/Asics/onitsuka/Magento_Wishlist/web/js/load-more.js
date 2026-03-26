/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/apply/main',
    'domReady!'
], function ($, mage) {
    'use strict';
    loadmoreProduct();
    $(window).scroll(function () {
        loadmoreProduct();
    });
    function loadmoreProduct() {
        if($('body').hasClass('wishlist-index-index')){
            if((window.innerHeight + window.scrollY) >= (document.body.offsetHeight - $('.page-footer-hader').height())) {
                if($('.page-wrapper .pages .item.current').length){
                    if(!$('.page-wrapper .pages .item.current').next().find('a.next').length){
                        if(!$('body').hasClass('loadmore-product')) {
                            var urlNext = $('.page-wrapper .pages .item.current').next().find('a').attr('href');
                            $('.page-wrapper .pages .item.current').next().addClass('current-tpm');
                            $('.page-wrapper .pages .item.current').removeClass('current');
                            $('.page-wrapper .pages .item.current-tpm').addClass('current').removeClass('current-tpm');
                            $('body').addClass('loadmore-product');
                            $.ajax({
                                url: urlNext,
                                type: 'get',
                                cache: false,
                                success: function (res) {
                                    $('.products-grid.wishlist .product-items').append($(res).find('.products-grid.wishlist .product-items').html());
                                    mage.apply();
                                    $('.products-grid.wishlist .product-items').trigger('contentUpdated');
                                    $('body').removeClass('loadmore-product');
                                }
                            });
                        }
                    }
                }
            }
        }
    }
});
