/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'mage/apply/main',
    'jquery-ui-modules/widget'
], function ($, $t, alert, mage) {
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
            form.find('.message-subscriber').hide();
            $('.newsletter-error').hide();
            $('.container-newsletter-err-msg').hide();
            if(form.valid()) {
                if (form.find('input[name="email"]').val()) {
                    this.ajaxSubmit(form);
                    return false;
                }
                $('.loadding-ajax.newsletter-loading,.container-newsletter-err-msg').removeClass('hidden');
                form.find('.message-subscriber').show();
                return false;
            }
            if ($('#newsletter').hasClass('error')) {
                form.find('.message-subscriber').show();
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
            $('body').addClass('active-ajax-loading');

            let adobeDataLayer = window.adobeDataLayer ? window.adobeDataLayer : [];

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
                                $('.container-newsletter-err-msg').parent().addClass('error-message');
                                $('.container-newsletter-err-msg').show();
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

                    // custom code for add adobe data layer start
                    if(res.pageInfo){
                        adobeDataLayer.push(
                            {
                                'event' : 'subscribe',
                                'page' : JSON.parse(res.pageInfo),
                                'userInfo' : res.userInfo
                            }
                        );
                    }
                    // custom code for add adobe data layer end

                    $(self.options.messagesSelector).trigger('contentUpdated');
                    $('body').addClass('active-ajax-loading');
                    mage.apply();
                },
            });
            window.adobeDataLayer = adobeDataLayer;
        },
    });

    return $.mage.newsletterAjax;
});
