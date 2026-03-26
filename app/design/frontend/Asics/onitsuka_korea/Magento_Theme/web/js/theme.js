/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/smart-keyboard-handler',
    'swipeSlider',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'mage/mage',
    'domReady!',
    'Amasty_Base/vendor/slick/slick.min',
    'js/select2'
], function ($, keyboardHandler, Swiper, $t, modal) {
    'use strict';
    $t("The order wasn't placed. First, agree to the terms and conditions, then try placing your order again.");
    $t("The coupon code isn't valid. Verify the code and try again.");
    $t("WIDTH");
    $t("Size");
    $t("Colors");
    $t("Invalid Form Key. Please refresh the page.");
    if ($('body').hasClass('catalog-product-view')) {
        if($(window).width() > 768 && ((jQuery(window).height() - 115) < $('.block-variation-container').height())) {
            $('.content-wrap.item-detail .area-content .inner .block-variation').addClass('making-top');
        }
    }
    footerBox();
    $(window).on( "resize", function() {
        footerBox();
    });
    $("body").on('mouseenter', '.field.date', function() {
        if($(this).find('#dob').attr('readonly')){
            $(this).find('#dob').removeAttr('readonly');
        }
    });
    const timerRecommendations = setInterval(function () {
     var LenthCountSlickN = $('.cms-page-not-found .homepage-recommendation .slick-track .slick-slide').length;
        if (LenthCountSlickN >= 1) {
            clearInterval(timerRecommendations);
             $('.cms-page-not-found .homepage-recommendation h2').show();
             $('.cms-page-not-found .homepage-recommendation h2').addClass('show-title');
        }
    }, 500);
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

    /*custom font use*/
    $(document).ready(function () {
      const koreanWord = /([\u1100-\u11FF\u3130-\u318F\uAC00-\uD7AF]+)/g;
      function wrapKoreanTextNodes(node) {
        $(node).contents().each(function () {
          if (this.nodeType === Node.TEXT_NODE) {
            const text = this.nodeValue;
            if (koreanWord.test(text) && text !== "줄이기…" && text !== "더 보기…") {
              const koreanWordText = text.replace(koreanWord, "<tt class='my-font'>$1</tt>");
              $(this).replaceWith(koreanWordText);
            }
          } else {
            wrapKoreanTextNodes(this); // Recurse into children
          }
        });
      }

      function hasOwnKoreanText(element) {
        let hasKorean = false;
        $(element).contents().each(function () {
          if (this.nodeType === Node.TEXT_NODE) {
            const cleaned = this.nodeValue
              .replace(/[0-9\-()\/:.,@ ]+/g, '')
              .replace(/[ㅣ|]+/g, '')
              .replace(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g, '');
            if (/[\u1100-\u11FF\u3130-\u318F\uAC00-\uD7AF]/.test(cleaned)) {
              hasKorean = true;
              return false;
            }
          }
        });
        return hasKorean;
      }

      wrapKoreanTextNodes(document.body);

      $('body *').each(function () {
        if (hasOwnKoreanText(this)) {
          $(this).addClass('korean-text');
        }
      });
    });
    /*custom font use*/    

    if ($('body').hasClass('checkout-cart-index')) {
        if ($('#co-shipping-method-form .fieldset.rates').length > 0 &&
            $('#co-shipping-method-form .fieldset.rates :checked').length === 0
        ) {
            $('#block-shipping').on('collapsiblecreate', function () {
                $('#block-shipping').collapsible('forceActivate');
            });
        }
    }
    $('.page-wrapper .page-header .page-header-block-search .search-button').on("click", function (e) {
        $('.page-wrapper .page-header .page-header-block-search .block-search').toggleClass('active')
    });
    $('.box-select-btn').on("click", function (e) {
        $(this).parent().toggleClass('active');
    });
    function i(e) {
        if (0 != e.length) {
            var t = e.offset().top
                , i = 0 != $('.page-header-sticky').length ? $('.page-header-sticky').height() : 0;
            414 < $(window).width() && (t -= i),
                $("html,body").animate({
                    scrollTop: t - 100
                }, 1e3)
        }
    }
    $('a.anchor-scroll').on("click", function (e) {
        var t = $(this).attr("href");
        0 === t.search("#") && (e.preventDefault(),
            i($(t)));
    });
    $('.newsletter-container select#day-picker').select2({
        dropdownParent: $('.newsletter-container select#day-picker').parent()
    });
    $('.newsletter-container select#year-picker').select2({
        dropdownParent: $('.newsletter-container select#year-picker').parent()
    });
    $('.newsletter-container select#month-picker').select2({
        dropdownParent: $('.newsletter-container select#month-picker').parent()
    });
    $('.account-nav .item.current strong').on("click", function (e) {
        window.location.reload();
    });
    $('.close-menu').on("click", function (e) {
        $('.nav-toggle').trigger('click');
    })
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
        $(this).parents('.footer-nav-list').addClass('active-nav');
        $.each( $('.footer.content .footer-nav-list:not(".active-nav")'), function( key, value ) {
            $(value).find('h4').removeClass('active');
            $(value).find('.box-accordion').removeClass('active');
        });
        $(this).toggleClass('active');
        $(this).next().toggleClass('active');
        $(this).parents('.footer-nav-list').removeClass('active-nav');
    });
    $('.box-guide-product-group span').off('click').on("click", function (e) {
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Size Guide',
            modalClass: 'size-guide-modals',
            buttons: [{
                text: $.mage.__('Submit'),
                click: function () {
                    this.closeModal();
                }
            }]
        };

        var popup = modal(options, $('.sizing-table'));

        $('.sizing-table').modal('openModal');
    });
    $('.back-top,.scrollToTop').on("click", function (e) {
        $("html, body").animate({ scrollTop: 0 }, 100);
    });
    $('.label-increase').on("click", function (e) {
        var currentValue =  parseInt($(this).parent().find('input').attr('data-qty'));
        var valueQty = parseInt($(this).parent().find('input').val())+1;
        $(this).parents('.cart-qty').find('.message-notice').removeClass('hidden');
        if ($(this).parents('.cart-qty').length) {
            $(this).parents('.cart-qty').find('.message-sales-qty').hide();
            var qtySales = $(this).parents('.cart-qty').attr('data-sales-qty');
            if(qtySales) {
                if (valueQty > qtySales) {
                    var message = $t('Only %1 Left Can Purchase').replace('%1', qtySales);
                    $(this).parents('.cart-qty').find('.message-sales-qty').text(message);
                    $(this).parents('.cart-qty').find('.message-notice').addClass('hidden');
                    $(this).parents('.cart-qty').find('.message-sales-qty').show();
                    $(this).parents('.cart-qty').find('.message-sales-qty').removeClass('hidden');
                    valueQty -=1;
                }
            }
        }
        $(this).parent().find('input').val(parseInt(valueQty)).trigger('change');
        $(this).parent().next().hide();
        if(currentValue != valueQty) {
            $(this).parent().next().show();
        }
    });
    $('.label-decrease').on("click", function (e) {
        var currentValue =  parseInt($(this).parent().find('input').attr('data-qty'));
        var valueQty = Math.max(1, parseInt($(this).parent().find('input').val()) - 1);
        $(this).parent().find('input').val(parseInt(valueQty)).trigger('change');
        $(this).parents('.cart-qty').find('.message-notice').removeClass('hidden');
        $(this).parent().next().hide();
        if(currentValue != valueQty) {
            $(this).parent().next().show();
        }
        if ($(this).parents('.cart-qty').length) {
            $(this).parents('.cart-qty').find('.message-sales-qty').hide();
            var qtySales = $(this).parents('.cart-qty').attr('data-sales-qty');
            if(qtySales) {
                if (valueQty > qtySales) {
                    var message = $t('Only %1 Left Can Purchase').replace('%1', qtySales);
                    $(this).parents('.cart-qty').find('.message-sales-qty').text(message);
                    $(this).parents('.cart-qty').find('.message-notice').addClass('hidden');
                    $(this).parents('.cart-qty').find('.message-sales-qty').removeClass('hidden');
                    $(this).parents('.cart-qty').find('.message-sales-qty').show();
                }
            }
        }
    });
    $('.footer-signup-newsletter , .footer.content .contact-wrapper > h4').on("click", function (e) {
        $('.footer.content .contact-wrapper').toggleClass('active');
    });
    var sticky = $('.header.content').offset().top;
    if($('body').hasClass('catalog-product-view')) {
        var e = document.querySelector(".block-btn-sp");
        if(e){
            document.querySelector(".box-tocart .actions").getBoundingClientRect().top - 50 < window.innerHeight ? e.classList.add("is-hidden") : e.classList.remove("is-hidden");
        }
    }
    scrollEvent();
    $(window).on( "scroll", function() {
        scrollEvent();
    });
    function scrollEvent(){
        if (window.pageYOffset >= sticky) {
            $('body').addClass('sticky-header');
            $(".scrollToTop").fadeIn("slow");
        } else {
            $('body').removeClass('sticky-header');
            $(".scrollToTop").fadeOut("slow");
        }
        if($('body').hasClass('catalog-product-view')){
            var e = document.querySelector(".content-wrap");
            if (!e) return;
            var t = e.getBoundingClientRect().top,
                s = document.querySelector(".js-slider"),
                i = s.clientHeight,
                a = s.getBoundingClientRect().top + i,
                n = document.querySelectorAll(".js-sticky-element"),
                r = Array.prototype.slice.call(n, 0);
            false ? r.forEach(function (e, t) {
                e.classList.remove("is-fixed");
            }) : t >= 0 ? r.forEach(function (e, t) {
                e.classList.remove("is-fixed"), e.classList.remove("is-bottomed");
            }) : a <= window.innerHeight ? r.forEach(function (e, t) {
                e.classList.remove("is-fixed"), e.classList.add("is-bottomed");
            }) : r.forEach(function (e, t) {
                e.classList.add("is-fixed"), e.classList.remove("is-bottomed");
            });
            if($(window).width() < 768) {
                var e = document.querySelector(".block-btn-sp");
                document.querySelector(".box-tocart .actions").getBoundingClientRect().top - 50 < window.innerHeight ? e.classList.add("is-hidden") : e.classList.remove("is-hidden");
            }
            if($(window).width() > 768) {
                var marginTop = (window.innerHeight + window.scrollY) - (document.body.offsetHeight - $('.page-footer-hader').height()) + $(window).height() - 200;
                $('.content-wrap.item-detail .area-content .inner .block-variation.making-top').css({'margin-top':''});
                var maximumTop = ($('.block-variation-container').height() - $(window).height()) + 120
                if(marginTop > 0) {
                    marginTop = Math.min(marginTop , maximumTop);
                    $('.content-wrap.item-detail .area-content .inner .block-variation.making-top').css({'margin-top':'-'+marginTop+'px'});
                }
            }
        }
        if($('body').hasClass('page-products')){
            var windowScroll = $('.page-footer-hader').offset().top - window.pageYOffset;
            if(windowScroll < 950) {
                $('.content-wrap.item-detail .area-content').addClass('is-fixed');
            }
        }
    }
    $('.tiger-container #fbsection10 .col-sm-4 > div').on("click", function (e) {
        var contentContainer = $(this).find('.container-popup').html();
        var heightContent = $('.tiger-container #fbsection10 .content .container').height();
        if($(this).find('.container-popup .iframe').length){
            contentContainer = '<iframe src="'+$(this).find('.container-popup .iframe').attr('data-src')+'" height="'+heightContent+'" frameborder="0" webkitallowfullscreen="" mozallowfullscreen="" allowfullscreen=""></iframe>';
        }
        $('.tiger-container #fbsection10 #future_content #future_inside').html(contentContainer);
        if($(this).find('.container-popup .slider').length && !$(this).find('.container-popup .slider .slick-list').length) {
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
    $('.tiger-container #fbsection10 #future #future_close').on("click", function (e) {
        $('.tiger-container #fbsection10 #future').toggleClass('active');
        $('body').toggleClass('active-popup-tiger');
        e.preventDefault();
        return false;
    });
    $('.search-box-mobile').on("click", function (e) {
        var itemMenu = $(this).parent().prev();
        $(itemMenu).next().toggleClass('active-show');
        $(itemMenu).toggleClass('ui-state-active-content');
        $(itemMenu).parent().toggleClass('ui-state-active-content');
        e.preventDefault();
        return false;
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
    if($('.header-alert .slider-content').length && !$('.header-alert .slick-list').length) {
        $('.header-alert .slider-content').slick({
            infinite: true,
            dots: false,
            autoplay: true,
            arrows: false,
            autoplaySpeed: 5000,
            slidesToShow: 1,
            slidesToScroll: 1
        });
    }
    if($('.message-alert-container .header-alert__item').length >1 && !$('.message-alert-container .slick-list').length){
        $('.message-alert-container .slider-content').slick({
            infinite: true,
            dots: false,
            autoplay: true,
            arrows: false,
            autoplaySpeed: 5000,
            slidesToShow: 1,
            slidesToScroll: 1
        });
    }
    function footerBox() {
        if($(window).width() <768) {
            $('.footer-box-wrapper').insertAfter('.page-footer .footer.block-bottom .box-copyright p:first-child');
        }else{
            $('.footer-box-wrapper').insertAfter('.page-footer .footer-box-wrapper-container');
        }
    }

    if($('.sizing-table').length > 0) {
        //$('.product-options-bottom .box-tocart').find('.box-guide').addClass('box-guide__show');
        $('.product-info-price-details').find('.product-info-price').addClass('product-info-price__custom')
    }else {
        //$('.product-options-bottom .box-tocart').find('.box-guide').removeClass('box-guide__show');
        $('.product-info-price-details').find('.product-info-price').removeClass('product-info-price__custom')
    }
});
