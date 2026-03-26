/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'mage/apply/main',
    'jquery-ui-modules/widget'
], function ($, $t, modal, mage) {
    'use strict';

    $.widget('mage.accountRemove', {
        modalRemove : null,
        options: {
            messagesSelector: '.page.messages'
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            this.initModal();
            $(self.element).off('click').on('click', function (e) {
                self._openModal();
                e.preventDefault;
                return false;
            });
        },
        /**
         * init Address Popup Modal
         */
        initModal: function () {
            var self = this;
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Delete My account'),
                modalClass: 'address-edit-popup',
                buttons: [{
                    text: $.mage.__('계속하기'),
                    class: '',
                    click: function () {
                        self.removeAccount(self);
                    }
                }]
            };
            this.modalRemove = modal(options, $('#modal-remove-account'));
        },
        _openModal: function () {
            var self = this;
            this.modalRemove.openModal();
        },
        removeAccount: function (self) {
            window.location.href = self.options.deleteUrlPrefix;
        }
    });

    return $.mage.accountRemove;
});
