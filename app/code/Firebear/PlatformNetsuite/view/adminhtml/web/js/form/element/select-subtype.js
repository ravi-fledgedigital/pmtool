define([
    'Magento_Ui/js/form/element/select'
], function (Select) {
    'use strict';
    return Select.extend(
        {
            defaults: {
                isShown: false,
                inverseVisibility: false,
                visible:false
            },
            toggleVisibility: function (selected) {
                this.isShown = selected in this.valuesForOptions;
                this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
            },
        });
});
