/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'jquery-ui-modules/widget'
], function ($, $t, modal) {
    'use strict';

    $.widget('mage.orderAjax', {
        modalAddress : null,
        addressList : null,
        addressId : null,
        address : null,
        options: {
            processStart: null,
            processStop: null,
            detailsSelector: '[data-role="order-details"]',
            returnSelector: '[data-role="order-return"]',
            messagesSelector: '.page.messages',
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            $(self.options.detailsSelector).off('click').on('click', function (e) {
                self.ajaxDetails($(e.currentTarget).data('order'));
                e.preventDefault;
                return false;
            });
            $(self.options.returnSelector).off('click').on('click', function (e) {
                self.ajaxDetails($(e.currentTarget).data('return'));
                e.preventDefault;
                return false;
            });
        },
        /**
         * @return {Boolean}
         */
        isLoaderEnabled: function () {
            return this.options.processStart && this.options.processStop;
        },
        /**
         *
         * @param $url
         */
        ajaxDetails: function ($url) {
            var self = this;

            $.ajax({
                url: $url,
                type: 'get',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                /** @inheritdoc */
                beforeSend: function () {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStart);
                    }
                },
                complete: function (res) {
                    $('.column.main').html($(res.responseText).find('.column.main').html());
                    $('.column.main').trigger('contentUpdated');
                    $('.column.main').find(self.options.detailsSelector).off('click').on('click', function (e) {
                        self.ajaxDetails($(e.currentTarget).data('order'));
                        e.preventDefault;
                        return false;
                    });
                    var options = {
                        type: 'popup',
                        title: 'Popup title',
                        trigger: '[data-trigger=trigger]',
                        responsive: true,
                        modalClass: 'shipment-track-popup',
                        buttons: [{
                            text: jQuery.mage.__('Submit'),
                            class: 'action'
                        }]
                    };
                    if($('#modal-shipment').length){
                        var popup = modal(options, $('#modal-shipment'));
                        $('.column.main').find(self.options.returnSelector).off('click').on('click', function (e) {
                            self.ajaxDetails($(e.currentTarget).data('return'));
                            e.preventDefault;
                            return false;
                        });
                    }
                    $('html, body').animate({
                        scrollTop: $('.column.main').offset().top - 100
                    }, 1000);
                },
                /** @inheritdoc */
                error: function (res) {
                }
            });
        },

    });

    return $.mage.orderAjax;
});
