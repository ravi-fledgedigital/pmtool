define([
    'uiCollection',
    'underscore',
    'jquery',
    'uiLayout',
    'mageUtils',
    'Magento_Ui/js/modal/alert',
    'ko',
    'mage/translate'
], function (Collection, _, $, layout, utils, malert) {
    'use strict';

    return Collection.extend({
        defaults: {
            template: 'Amasty_Rma/return/order',
            indexedReasons: [],
            indexedConditions: [],
            indexedResolutions: [],
            elemIndex: 0,
            listens: {
                '${ $.provider }:data.validate': 'validate'
            },
            links: {
                items: '${ $.provider }:${ $.dataScope }.return_items'
            }
        },
        initialize: function () {
            this._super();

            _.each(this.reasons, function (reason) {
                this.indexedReasons[reason.value] = reason.label;
            }.bind(this));
            _.each(this.resolutions, function (resolution) {
                this.indexedResolutions[resolution.value] = resolution.label;
            }.bind(this));
            _.each(this.conditions, function (condition) {
                this.indexedConditions[condition.value] = condition.label;
            }.bind(this));

            return this;
        },

        initObservable: function () {
            this._super().observe(['items']);

            return this;
        },

        initLinks: function () {
            this._super();

            _.each(this.items(), function (item) {
                item = this.createItem(item, this.elemIndex);
                layout([
                    item
                ]);
                this.insertChild(item.name);
                this.elemIndex += 1;
            }.bind(this));

            return this;
        },

        createItem: function (item, index) {
            return utils.extend({}, {
                'items': item,
                'name': 'return-item-' + index,
                'component': 'Amasty_Rma/js/return/order-item'
            });
        },


        validate: function () {
            var purchQty,
                productSku,
                splitedReturnQty,
                itemsToReturn = [],
                valid = true;

            _.each(this.elems(), function (elem) {
                if (valid) {
                    if (!elem.validate()) {
                        valid = false;
                    } else {
                        if (elem.qty > 0.0001) {
                            itemsToReturn.push(elem.items());
                        }
                    }
                }
            }.bind(this));

            if (valid && itemsToReturn.length === 0) {
                this.validateError($.mage.__('There are no items to return.'));
                valid = false;
            }

            if (!valid) {
                this.source.set('params.invalid', true);

                return false;
            }

            _.map(itemsToReturn, function (items) {
                return _.map(items, function (item) {
                    item.reason_id = item.reason_id();

                    return item;
                })
            });

            this.items(itemsToReturn);
        },

        validateError: function (message) {
            malert({
                title: 'Error',
                content: message,
                clickableOverlay: false,
                actions: {
                    always: function () {
                    }
                }
            });
        }
    });
});
