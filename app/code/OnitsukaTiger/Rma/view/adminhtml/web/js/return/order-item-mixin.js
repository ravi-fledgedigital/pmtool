define([
    'uiElement',
    'underscore',
    'jquery',
    'ko',
    'mage/translate'
], function (Element, _, $, ko) {
    'use strict';
    return function (Form) {
        return Form.extend({
            validate: function () {
                var valid = true;
                this.qty = 0;
                var request_qty_new = parseInt(this.items()[0].qty) - parseInt(this.items()[0].request_qty)
                _.each(this.items(), function (productRow) {
                    if (productRow.is_returnable && parseFloat(productRow.qty) > 0.0001) {
                        if (productRow.resolution_id == 0 || productRow.reason_id() == 0 || productRow.condition_id == 0) {
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
                    if (_.isUndefined(this.items()[0].returnable_qty) || window.event.currentTarget.id === 'save' || !Number.isInteger(this.qty)) {
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
                    } else {
                        if (parseFloat(request_qty_new) > parseFloat(this.items()[0].returnable_qty)) {
                            valid = false;
                            this.containers[0].validateError(
                                $.mage.__('Return Qty more than Available Qty for %1')
                                    .replace('%1', this.items()[0].name)
                            );
                        }
                        else if (parseFloat(this.items()[0].qty) === 0)
                        {
                            valid = false;
                            this.containers[0].validateError(
                                $.mage.__('There are no items to return.\n')
                            );
                        }
                    }
                }

                return valid;
            },
        });
    }
});
