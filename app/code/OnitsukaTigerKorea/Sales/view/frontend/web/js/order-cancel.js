/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    "Magento_Ui/js/modal/modal",
    'underscore',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/model/messageList',
    'mage/template',
    'jquery-ui-modules/widget'
], function ($,$t,modal,_,customerData) {
    'use strict';
    $.widget('mage.orderCancel', {

        options: {
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            $('.form-cancel #reason').on('change', function() {
                $('.reason-value').val(this.value);
                if(this.value == 'other') {
                    $('.other-options').removeClass('hidden');
                    $('.reason-value').val('');
                } else {
                    $('.other-options').addClass('hidden');
                }
                $('#modal-cancel-order .message-error').addClass('hidden');
            });
            $('.form-cancel .other-options').on('change', function() {
                $('.reason-value').val(this.value);
            });
            $('#cancel-order').off('click').on('click', function () {
                if($('#modal-cancel-order').hasClass('hidden')) {
                    var popup = modal({
                        type: 'popup',
                        responsive: true,
                        title: $.mage.__('Are you sure you want to cancel your order?'),
                        buttons: [{
                            text: $.mage.__('Yes'),
                            class: 'action primary',
                            click: function () {
                                if(!$('.reason-value').val()) {
                                    $('#modal-cancel-order .message-error').removeClass('hidden');
                                } else {
                                    this.closeModal();
                                    self.cancelOrder($('.reason-value').val());
                                }
                            }
                        }]
                    }, $('#modal-cancel-order'));
                    $('#modal-cancel-order').removeClass('hidden');
                }
                $('#modal-cancel-order').modal('openModal');
                return false;
            });
            $('a[data-role="order-cancel"]').off('click').on('click', function () {
                var $this = $(this);
                if($('#modal-cancel-order').hasClass('hidden')) {
                    var popup = modal({
                        type: 'popup',
                        responsive: true,
                        title: $.mage.__('Are you sure you want to cancel your order?'),
                        buttons: [{
                            text: $.mage.__('Yes'),
                            class: 'action primary',
                            click: function () {
                                if(!$('.reason-value').val()) {
                                    $('#modal-cancel-order .message-error').removeClass('hidden');
                                } else {
                                    this.closeModal();
                                    self.cancelOrderLogin($this.data('order'),$('.reason-value').val());
                                }
                            }
                        }]
                    }, $('#modal-cancel-order'));
                    $('#modal-cancel-order').removeClass('hidden');
                }
                $('#modal-cancel-order').modal('openModal');
                return false;
            });

        },
        showingMessage: function(){
            var cookieMessages = _.unique($.cookieStorage.get('mage-messages'), 'text');
            $.each( cookieMessages, function( index, value ) {
                var htmlMessage = $('.message-hidden-ajax .messages');
                htmlMessage.find('.message').addClass('message-'+value.type).addClass(value.type);
                htmlMessage.find('.message > div').text($t(value.text));
                $('.page.messages div:last-child').html(htmlMessage);
            });
            customerData.set('messages', {});
            $.mage.cookies.set('mage-messages', '', {
                samesite: 'strict',
                domain: ''
            });
        },
        cancelOrderLogin: function (id, reason){
            var self = this;
            $('body').addClass('active-ajax-loading');
            $.ajax({
                url: self.options.actionUrlPrefix,
                data: {
                    'oar_order_id': id,
                    'reason':  reason
                },
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function (res) {
                    $('body').removeClass('active-ajax-loading');
                    if(res.success){
                        $('.page-main').trigger('contentUpdated');
                        $('.column.main').find(self.options.detailsSelector).off('click').on('click', function (e) {
                            self.ajaxDetails($(e.currentTarget).data('order'));
                            e.preventDefault;
                            return false;
                        });
                        if($('#modal-shipment').length){
                            $('.column.main').find(self.options.returnSelector).off('click').on('click', function (e) {
                                self.ajaxDetails($(e.currentTarget).data('return'));
                                e.preventDefault;
                                return false;
                            });
                        }
                        $('.order-details-items.ordered .order-details-container .order-head .order-details').html(res.statusOrder);
                        $('a[data-role="order-cancel"]').remove();
                        $('#cancel-order').remove();
                    }
                    self.showingMessage();
                    $('html, body').animate({
                        scrollTop: $('.page-main').offset().top - 150
                    }, 1000);
                },
                error: function (res) {
                    console.log(res);
                    $('body').removeClass('active-ajax-loading');
                }
            });
        },
        /**
         * ajax handle call
         */
        cancelOrder: function (reason){
            var self = this;
            $.ajax({
                url: self.options.actionUrlPrefix,
                data: {
                    'oar_order_id': $('#oar-order-id').val(),
                    'oar_email':  $('#oar_email').val(),
                    'reason':  reason,
                    'isAjax': 1,
                },
                type: 'post',
                dataType: 'json',
                showLoader: true,
                cache: false,
                success: function (res) {
                    if(res.success){
                        $('.page-main').trigger('contentUpdated');
                        $('a[data-role="order-cancel"]').remove();
                        $('#cancel-order').remove();
                        $('.order-details-items.ordered .order-details-container .order-head .order-details').html(res.statusOrder);
                    }
                    self.showingMessage();
                },
                error: function (res) {
                    console.log(res);
                }
            });
        },
    });

    return $.mage.orderCancel;
});

