/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/smart-keyboard-handler',
    'swipeSlider',
    'mage/translate',
    'mage/mage',
    'domReady!',
    'Amasty_Base/vendor/slick/slick.min',
    'js/select2'
], function ($, keyboardHandler, Swiper, $t) {
    'use strict';
    $t("The order wasn't placed. First, agree to the terms and conditions, then try placing your order again.");
    var mySwiper = new Swiper('.banner-slide .swiper-container', {
        allowTouchMove: !0,
        slidesPerView: 1,
        spaceBetween: 5,
        navigation: {
            nextEl: ".banner-slide .button-next",
            prevEl: ".banner-slide .button-prev"
        },
        breakpoints: {
            769: {
                allowTouchMove: !1,
                slidesPerView: 3,
                spaceBetween: 2
            }
        }
    });
    if ($('body').hasClass('checkout-cart-index')) {
        if ($('#co-shipping-method-form .fieldset.rates').length > 0 &&
            $('#co-shipping-method-form .fieldset.rates :checked').length === 0
        ) {
            $('#block-shipping').on('collapsiblecreate', function () {
                $('#block-shipping').collapsible('forceActivate');
            });
        }
    }
    $('.tiger-container #fbsection10 .col-sm-4 > div').click(function (e) {
        var contentContainer = $(this).find('.container-popup').html();
        var heightContent = $('.tiger-container #fbsection10 .content .container').height();
        if($(this).find('.container-popup .iframe').length){
            contentContainer = '<iframe src="'+$(this).find('.container-popup .iframe').attr('data-src')+'" height="'+heightContent+'" frameborder="0" webkitallowfullscreen="" mozallowfullscreen="" allowfullscreen=""></iframe>';
        }
        $('.tiger-container #fbsection10 #future_content #future_inside').html(contentContainer);
        if($(this).find('.container-popup .slider').length) {
            $('.tiger-container #fbsection10 #future_content #future_inside .slider').slick({
                infinite: true,
                dots: false,
                autoplay: false,
                arrows: true,
                fade: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                cssEase: 'cubic-bezier(0.445, 0.05, 0.55, 0.95)',
            });
        }
        $('.tiger-container #fbsection10 #future').toggleClass('active');
        $('body').toggleClass('active-popup-tiger');
        e.preventDefault();
        return false;
    });
    $('.tiger-container #fbsection10 #future #future_close').click(function (e) {
        $('.tiger-container #fbsection10 #future').toggleClass('active');
        $('body').toggleClass('active-popup-tiger');
        e.preventDefault();
        return false;
    });
    $('.newsletter-container select').select2();
    $('.account-nav .item.current strong').on("click", function (e) {
        window.location.reload();
    });
    $('.account-nav .account-nav-title').append('<select onchange="javascript:location.href=this.value"></select>');
    $('.account-nav .item').each(function () {
        if($(this).hasClass('current')){
            $('.account-nav .account-nav-title select').append('<option selected="selected" value=" ">'+$(this).find('strong').text()+'</option>')
        }else{
            $('.account-nav .account-nav-title select').append('<option value="'+$(this).find('a').attr('href')+'">'+$(this).find('a').text()+'</option>');
        }
    });
    $('.page-layout-cms-size-guide').addClass('page-layout-2columns-left');
    $('.footer.content .footer-nav-list h4').on("click", function (e) {
        $(this).parent().addClass('active-nav');
        $('.footer.content .footer-nav-list:not(".active-nav") h4').removeClass('active');
        $('.footer.content .footer-nav-list:not(".active-nav") ul').removeClass('active');
        $(this).toggleClass('active');
        $(this).next().toggleClass('active');
        $(this).parent().removeClass('active-nav');
    });
    $('.back-top,.scrollToTop').click(function(){
        $("html, body").animate({ scrollTop: 0 }, 100);
    });
    $('.label-increase').click(function(){
        var currentValue =  parseInt($(this).parent().find('input').attr('data-qty'));
        var valueQty = parseInt($(this).parent().find('input').val())+1;
        $(this).parent().find('input').val(parseInt(valueQty));
        $(this).parent().next().hide();
        if(currentValue != valueQty) {
            $(this).parent().next().show();
        }
    });
    $('.label-decrease').click(function(){
        var currentValue =  parseInt($(this).parent().find('input').attr('data-qty'));
        var valueQty = Math.max(1, parseInt($(this).parent().find('input').val()) - 1);
        $(this).parent().find('input').val(parseInt(valueQty));
        $(this).parent().next().hide();
        if(currentValue != valueQty) {
            $(this).parent().next().show();
        }
    });
    $('.footer-signup-newsletter , .footer.content .contact-wrapper > h4').click(function(){
        $('.footer.content .contact-wrapper').toggleClass('active');
    });
    var sticky = $('.header.content').offset().top;
    $(window).scroll(function () {
        if (window.pageYOffset >= sticky) {
            $('body').addClass('sticky-header');
            $(".scrollToTop").fadeIn("slow");
        } else {
            $('body').removeClass('sticky-header');
            $(".scrollToTop").fadeOut("slow");
        }
    });
    $('.account-nav-title strong').text($('.account-nav .item.current strong').text());
    $('.account-nav-title').on("click", function (e) {
        $(this).next().toggleClass('active');
    });
    $('.block.newsletter .policy-checkbox input[type="checkbox"]').on("change", function (e) {
        if($(this).is(":checked")){
            $('.block.newsletter .action.subscribe').removeAttr("disabled");
        }else {
            $('.block.newsletter .action.subscribe').attr("disabled" , "disabled");
        }

    });
    $('.cart-summary').mage('sticky', {
        container: '#maincontent'
    });
    $('.cms-page-view .page-main .sidebar-main ul li').each(function () {
        var href = window.location.protocol + '//' + window.location.hostname + window.location.pathname + window.location.hash;
        if(href === $(this).find('a').attr('href')){
            $(this).addClass('current');
        }
    });
    $('.panel.header > .header.links').clone().appendTo('#store\\.links');
    $('.page-products .page-title span').text($('.page-products .page-title span').text() +' ('+$('.toolbar:first-child .toolbar-amount .toolbar-number.total').text()+')');
    $('.mobile-sort-product').html($('.toolbar:first-child .toolbar-sorter').html());
    $('.block.filter .filter-content > .block-subtitle').on("click", function (e) {
        $('.mobile-sort-product + .filter-options').toggle();
    });
    if($('.current-filter-container #am-shopby-container').length) {
        $('.page-products .page-title-wrapper').show();
    }
    keyboardHandler.apply();
    $('.header-alert .slider-content').slick({
        infinite: true,
        dots: false,
        autoplay: true,
        arrows: false,
        autoplaySpeed: 5000,
        slidesToShow: 1,
        slidesToScroll: 1
    });
});
