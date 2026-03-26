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
            defaults: {
                template: 'Amasty_Rma/return/order-item',
                productTemplate: 'Amasty_Rma/return/product',
                stateTemplate: 'Amasty_Rma/return/state',
                editStateTemplate: 'Amasty_Rma/return/edit-state',
                viewFieldsTemplate: 'Amasty_Rma/return/view-fields',
                createFieldsTemplate: 'Amasty_Rma/return/create-fields',
                qty: 10,
                items: []
            }
        });
    }
});
