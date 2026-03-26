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
        daysInMonth: function (m, y) {
            switch (m) {
                case 1 :
                    return (y % 4 == 0 && y % 100) || y % 400 == 0 ? 29 : 28;
                case 8 : case 3 : case 5 : case 10 :
                    return 30;
                default :
                    return 31
            }
        },
        isValidDate: function (d, m, y) {
            m = parseInt(m, 10) - 1;
            return m >= 0 && m < 12 && d > 0 && d <= this.daysInMonth(m, y);
        },
        /**
         * Handler for the form 'submit' event
         *
         * @param {jQuery} form
         */
        submitForm: function (form) {
            form.find('.validation-age').addClass('hidden');
            form.find('.validation-message-result').addClass('hidden');
            form.find('div.mage-error').remove();
            if(form.valid()){
                form.find('.validation-checkbox div.mage-error').remove();
                var dateCustomer = form.find('#month-picker').val() + '/' + form.find('#day-picker').val() + '/'+form.find('#year-picker').val();
                var validateDate = this.isValidDate(form.find('#day-picker').val(),form.find('#month-picker').val(),form.find('#year-picker').val());
                var age = this.getAge(dateCustomer);
                form.find('.validate-days').hide();
                if(age < 16){
                    form.find('.validation-age').removeClass('hidden');
                    form.find('.validation-age span').show();
                    form.find('.validation-age .validate-days').hide();
                    return false;
                }
                if(!validateDate) {
                    form.find('.validation-age').removeClass('hidden');
                    form.find('.validation-age span').hide();
                    form.find('.validate-days').show();
                    return false;
                }
                form.find('#dob').val(dateCustomer);
                this.ajaxSubmit(form);
            }
            form.find('.validation-checkbox div.mage-error').remove();
            var htmlValidation = '<div id="term-conditions-error" class="mage-error" style="">'+form.find('input#term-conditions').data('msg-required')+'</div>';
            form.find('.validation-checkbox').append(htmlValidation);
            return false;
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
            $('body').addClass('active-ajax-loading');
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
                            $('.validation-message-result').html('<span>'+res.message+'</span>').removeClass('hidden');
                        }else{
                            var htmlSuccess = '<div class="success-message-popup">'+res.popupMessage+'</div>';
                            $('.promotion-popup .newsletter-container').replaceWith(htmlSuccess);
                        }
                        $.cookieStorage.set('mage-messages', '');
                        self.options.processStart = null;

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
                    }
                    $(self.options.messagesSelector).trigger('contentUpdated');
                    $('body').addClass('active-ajax-loading');
                    mage.apply();
                },
            });
        },

    });

    return $.mage.newsletterAjax;
});
