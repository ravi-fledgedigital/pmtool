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
            form.find('.validation-age').addClass('hidden');
            form.find('.validation-message-result').addClass('hidden');
            if(form.valid()){
                var dateCustomer = form.find('#month-picker').val() + '/' + form.find('#day-picker').val() + '/'+form.find('#year-picker').val()
                var age = this.getAge(dateCustomer);
                if(age < 16){
                    form.find('.validation-age').removeClass('hidden');
                    return false;
                }
                form.find('#dob').val(dateCustomer);
                this.ajaxSubmit(form);
            }
        },
        getAge: function(dob){
            var today = new Date();
            var birthDate = new Date(dob);
            var age = today.getFullYear() - birthDate.getFullYear();
            var m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age;
        },
        /**
         * @param {jQuery} form
         */
        ajaxSubmit: function (form) {
            var self = this,
                formData;

            formData = new FormData(form[0]);
            $.ajax({
                url: form.attr('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                success: function (res) {
                    if(res.success){
                        if(res.error){
                            $('.validation-message-result span').text(res.message).parent().removeClass('hidden');
                        }else{
                            var htmlSuccess = '<div class="success-message-popup">'+res.popupMessage+'</div>';
                            $('.promotion-popup .newsletter-container').replaceWith(htmlSuccess);
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
