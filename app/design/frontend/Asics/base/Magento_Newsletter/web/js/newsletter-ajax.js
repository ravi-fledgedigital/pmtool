/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'jquery-ui-modules/widget'
], function ($, $t, alert) {
    'use strict';

    $.widget('mage.newsletterAjax', {
        options: {
            isFooter: null,
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            $(self.element).on('submit', function (e) {
                self.submitForm(self.element);
                e.preventDefault;
                return false;
            });
        },
        /**
         * Handler for the form 'submit' event
         *
         * @param {jQuery} form
         */
        submitForm: function (form) {
            if(form.valid()) {
                if (form.find('input[name="email"]').val()) {
                    this.ajaxSubmit(form);
                    return false;
                }
                form.find('.message-subscriber').show();
                return false;
            }
        },
        /**
         * @param {jQuery} form
         */
        ajaxSubmit: function (form) {
            var self = this,
                formData;

            formData = new FormData(form[0]);
            $('.loadding-ajax.newsletter-loading,.container-newsletter-err-msg').removeClass('hidden');
            $.ajax({
                url: form.attr('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                success: function (res) {
                    $('.loadding-ajax.newsletter-loading').addClass('hidden');
                    $('.container-newsletter-err-msg').parent().removeClass('error-message');
                    $('.message-subscriber').hide();
                    if(res.success){
                        if(self.element.hasClass('subscribe-page')){
                            if(res.error){
                                $('.container-newsletter-err-msg').text(res.message).removeClass('hidden');
                                $('.container-newsletter-err-msg').parent().addClass('error-message')
                            }else{
                                $('.container-newsletter-box').replaceWith(res.successHtml);
                            }
                        }else{
                            alert({
                                content: res.message,
                                modalClass: 'newsletter-message-alert',
                                buttons: [{
                                    text: $.mage.__('Ok'),
                                    class: 'action primary accept',
                                    click: function () {
                                        this.closeModal(true);
                                    }
                                }]
                            });
                        }
                        $.cookieStorage.set('mage-messages', '');
                        self.options.processStart = null;
                    }
                    $(self.options.messagesSelector).trigger('contentUpdated');
                },
            });
        },

    });

    return $.mage.newsletterAjax;
});
