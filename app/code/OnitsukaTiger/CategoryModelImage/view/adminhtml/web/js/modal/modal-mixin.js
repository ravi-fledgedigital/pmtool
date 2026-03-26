define(['jquery'], function($) {
    'use strict';
    return function(widget) {
        $.widget('mage.modal', $.mage.modal, {
            closeModal: function () {
                if(this.modal.find('.position-image').length) {
                    if(this.modal.find('.position-image').hasClass('error')){
                        return false;
                    }
                }
                var that = this;

                this._removeKeyListener();
                this.options.isOpen = false;
                this.modal.one(this.options.transitionEvent, function () {
                    that._close();
                });
                this.modal.removeClass(this.options.modalVisibleClass);

                if (!this.options.transitionEvent) {
                    that._close();
                }

                return this.element;
            }
        });
        return $.mage.modal;
    }
});
