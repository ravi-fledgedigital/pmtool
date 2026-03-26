define([
    'uiElement',
    'underscore',
    'jquery',
    'ko',
    'mage/translate'
], function (Element, _, $, ko) {
    'use strict';

    return Element.extend({
        defaults: {
            template: 'Amasty_Rma/return/order-item',
            productTemplate: 'Amasty_Rma/return/product',
            stateTemplate: 'Amasty_Rma/return/state',
            editStateTemplate: 'Amasty_Rma/return/edit-state',
            viewFieldsTemplate: 'Amasty_Rma/return/view-fields',
            createFieldsTemplate: 'Amasty_Rma/return/create-fields',
            qty: 0,
            items: []
        },
        initObservable: function () {
            _.map(this.items, function (item) {
                item.reason_id = ko.observable(item.reason_id);

                return item;
            });
            this._super().observe(['items']);

            return this;
        },
        split: function (index, element, item) {
            var newItem,
                reason_id = 0,
                qty = 0,
                products = this.items();

            _.each(products, function (product) {
                qty += parseFloat(product.qty);
            });

            newItem = _.clone(item);
            newItem.status = 0;
            reason_id = item.reason_id();
            newItem.reason_id = ko.observable(reason_id);
            newItem.request_item_id = 0;

            if (parseFloat(qty) < parseFloat(products[0].request_qty)) {
                newItem.qty = parseFloat(products[0].request_qty) - parseFloat(qty);
            } else {
                if (products[0].qty > 1) {
                    products[0].qty = parseFloat(products[0].qty) - 1;
                    newItem.qty = 1;
                } else {
                    return;
                }
            }

            this.updateItem(0, item);
            products.push(newItem);
            this.items(products);
        },

        delete: function (index) {
            var products = this.items();

            products[0].qty = parseFloat(products[0].qty) + parseFloat(products[index].qty);
            this.updateItem(0, products[0]);
            products.splice(index, 1);
            this.items(products);
        },

        edit: function (index, element, item) {
            item.is_editable = false;
            this.updateItem(index, item);
        },

        save: function (index, item) {
            item.is_editable = true;
            this.updateItem(index, item);
        },

        updateItem: function (index, item) {
            var products = this.items();

            products.splice(index, 1);
            this.items(products);
            products.splice(index, 0, item);
            this.items(products);
        },

        whoPays: function (reason_id) {
            var defaultMessage = '',
                reason,
                messages = [
                    $.mage.__('Customer is supposed to cover shipping costs'),
                    $.mage.__('Store is supposed to cover shipping costs'),
                    $.mage.__('Not set')
                ];

            if (!reason_id) {
                return defaultMessage;
            }

            reason = this.containers[0].reasons.find(function (reason) {
                return reason.value == reason_id;
            });

            if (!reason) {
                return defaultMessage;
            }

            return messages[reason.payer];
        },

        getNumericArray: function (num) {
            var result = [];

            for (var i = 0; i <= num; i++) {
                result.push(i);
            }

            return result;
        },

        validate: function () {
            var valid = true;
            this.qty = 0;

            _.each(this.items(), function (productRow) {
                if (productRow.is_returnable && parseFloat(productRow.qty) > 0.0001) {
                    if (productRow.condition_id == 0 || productRow.reason_id() == 0 || productRow.condition_id == 0) {
                        if (valid) {
                            valid = false;
                            this.containers[0].validateError(
                                $.mage.__('Condition/Reason/Resolution are required fields.')
                            );
                        }
                    } else if (valid) {
                        this.qty += parseFloat(productRow.qty);
                    }
                }
            }.bind(this));

            if (valid && this.qty > 0.0001) {
                if (parseFloat(this.items()[0].request_qty) < parseFloat(this.qty)) {
                    valid = false;
                    this.containers[0].validateError(
                        $.mage.__('Return Qty more than Available Qty for %1')
                            .replace('%1', this.items()[0].name)
                    );
                } else if (_.isUndefined(this.containers[0].is_createForm)
                    && parseFloat(this.items()[0].request_qty) !== parseFloat(this.qty)
                ) {
                    valid = false;
                    this.containers[0].validateError(
                        $.mage.__('The Amount of Return Qty less than Initial for %1. The Initial Qty is %2')
                            .replace('%1', this.items()[0].name)
                            .replace('%2', this.items()[0].request_qty)
                    );
                }
            }

            return valid;
        },
        checkStatus: function (index, status) {
            var item = this.items()[index];

            if (item.status === status) {
                item.status = 0;
            } else {
                item.status = status;
            }

            this.updateItem(index, item);
        },

        getProductTemplate: function () {
            return this.productTemplate;
        },

        getStateTemplate: function () {
            return this.stateTemplate;
        },

        getCreateFieldsTemplate: function () {
            return this.createFieldsTemplate;
        },

        getViewFieldsTemplate: function () {
            return this.viewFieldsTemplate;
        },

        getEditStateTemplate: function () {
            return this.editStateTemplate;
        }
    });
});
