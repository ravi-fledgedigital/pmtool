define([
    'jquery',
    'uiComponent',
    'mage/url',
    'mage/translate'
], function ($, Component, urlBuilder, $t) {
    return Component.extend({
        defaults: {
            idRowSelector: '#my-orders-table tbody .col.id',
            actionColumnSelector: '.col.actions',
            orderRelation: '',
            orderIds: {},
            newReturnUrl: 'rma/account/newreturn/'
        },

        initialize: function () {
            this._super();
            $.each(this.orderRelation.split(','), function (index, relation) {
                var orderRealId = relation.split('-oid')[0]

                this.orderIds[orderRealId] = relation.split('-oid')[1];
            }.bind(this));
            this.renderReturnLinks();

            return this;
        },

        renderReturnLinks: function () {
            var orderRows = $(this.idRowSelector);

            $.each(orderRows, function (rowIndex, row) {
                var rowOrderRealId = row.innerHTML;

                if (Object.keys(this.orderIds).indexOf(rowOrderRealId) !== -1) {
                    $(row).parent()
                        .find(this.actionColumnSelector)
                        .append(this.createLinkElement(this.orderIds[rowOrderRealId]));
                }
            }.bind(this));
        },

        createLinkElement: function (orderId) {
            return $('<a>', {
                text: $t('Return'),
                href: this.newReturnUrl + 'order/' + parseInt(orderId) + '/'
            });
        },
    });
});
