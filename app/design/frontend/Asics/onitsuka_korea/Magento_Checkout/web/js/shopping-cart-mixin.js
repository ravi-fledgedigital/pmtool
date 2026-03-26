define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'jquery-ui-modules/widget'
], function ($, confirm, $t) {
    'use strict';
    return function (widget) {
        $.widget('mage.shoppingCart', $.mage.shoppingCart, {
            _confirmClearCart: function () {
                var self = this;

                confirm({
                    content: $t('Are you sure you want to remove all items from your shopping cart?'),
                    actions: {
                        /**
                         * Confirmation modal handler to execute clear cart action
                         */
                        confirm: function () {
                            self.clearCart();
                        }
                    },
                    buttons: [{
                        text: $t('Cancel '),
                        class: 'action-secondary action-dismiss',

                        /**
                         * Click handler.
                         */
                        click: function (event) {
                            this.closeModal(event);
                        }
                    },
                        {
                            text: $t('OK '),
                            class: 'action-primary action-accept',

                            /**
                             * Click handler.
                             */
                            click: function (event) {
                                self.clearCart();
                            }
                        }]
                });
            },
        });
        return $.mage.shoppingCart;
    }
});
