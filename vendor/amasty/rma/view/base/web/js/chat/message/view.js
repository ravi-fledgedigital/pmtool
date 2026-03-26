define([
    'uiElement',
    'jquery',
    'Amasty_Rma/js/modal/alert/ammodal-popup',
    'mage/translate'
], function (Element, $, confirm) {
    'use strict';

    return Element.extend({
        defaults: {
            is_system: 0,
            is_manager: 0
        },
        initialize: function () {
            this._super();
            this.template = this.is_system ? 'Amasty_Rma/chat/message/system' : 'Amasty_Rma/chat/message/message';

            return this;
        },
        moveToBottom: function () {
            setTimeout(function () {
                jQuery('.amrma-chat-block').scrollTop(99999999999);
            }, 100)
        },
        deleteMessage: function () {
            confirm({
                content: $.mage.__('Are you sure you want to delete the message?'),
                actions: {
                    confirm: function () {
                        $.ajax({
                            url: this.deleteMessageUrl,
                            data: {message_id: this.message_id},
                            method: 'post',
                            global: false,
                            dataType: 'json'
                        });
                        this.destroy();
                    }.bind(this)
                }
            });
        }
    });
});
