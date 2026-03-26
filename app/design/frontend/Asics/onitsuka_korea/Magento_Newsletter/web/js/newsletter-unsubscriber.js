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

    $.widget('mage.newsletterUnsubscriber', {
        options: {
            isAjax: null,
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            var form = self.element;
            $(self.element).find('#newsletter-unsubscriber').on('paste keydown keyup', function() {
                form.find('.unsubscribe').attr('disabled','disabled');
                if (form.find('input[name="email"]').val()) {
                    form.find('.unsubscribe').removeAttr('disabled');
                }
            });
            $(self.element).on('submit', function (e) {
                form.find('.message-unsubscriber').hide();
                if(!form.valid()) {
                    form.find('.message-unsubscriber').show();
                    e.preventDefault();
                    return false;
                }
            });
        }
    });

    return $.mage.newsletterUnsubscriber;
});
