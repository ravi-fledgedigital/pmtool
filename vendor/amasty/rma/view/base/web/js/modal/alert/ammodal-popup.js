define([
    'jquery',
    'text!Amasty_Rma/template/modal/ammodal.html',
    'mage/translate',
    'jquery-ui-modules/widget',
    'Magento_Ui/js/modal/modal',
], function ($, amPopupTpl, $t) {
    'use strict';

    $.widget('mage.amRmaModal', $.mage.modal, {
        options: {
            type: 'amPopup',
            amPopupTpl: amPopupTpl,
            modalClass: 'confirm',
            actions: {
                always: function () {
                },
                confirm: function () {
                }
            },
            buttons: [{
                text: $t('Cancel'),
                class: 'action-secondary action-dismiss',

                /**
                 * Click handler.
                 */
                click: function (event) {
                    this.closeModal(event);
                }
            }, {
                text: $t('OK'),
                class: 'action-primary action-accept',

                /**
                 * Click handler.
                 */
                click: function (event) {
                    this.options.actions.confirm(event);
                    this.closeModal(event, true);
                }
            }]
        },
        _remove: function () {
            this.modal.remove();
        },
        closeModal: function () {
            this.options.actions.always();
            const originalTrigger = this._trigger;

            this._trigger = function (name) {
                if (name === 'closed') {
                    this._remove();
                }

                return originalTrigger.apply(this, arguments);
            };

            return this._super();
        }
    });

    return function (config) {
        return $('<div></div>').html(config.content).amRmaModal(config).amRmaModal('openModal');
    };
});
