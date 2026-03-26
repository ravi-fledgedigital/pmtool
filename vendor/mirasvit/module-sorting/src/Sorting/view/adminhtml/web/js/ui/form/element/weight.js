define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/element/abstract'
], function ($, _, Text) {
    return Text.extend({
        defaults: {
            elementTmpl: 'Mirasvit_Sorting/ui/form/element/weight',

            valueUpdate: 'keyup',

            listens: {
                value: 'onUpdateValue'
            }
        },

        initialize: function () {
            this._super();
            
            this.optionCount = Array.isArray(this?.source?.data?.config?.mapping)
                ? this.source.data.config.mapping.length
                : 100;

            this.maxValue = this.optionCount > 100 ? this.optionCount : 100;
            this.minValue = -this.maxValue;

            _.bindAll(this, 'onUpdateValue');
        },

        onUpdateValue: function () {
            var val = this.value();

            if (val !== '-' && val !== '') {
                if (isNaN(parseInt(val))) {
                    this.value(0);
                } else if (val !== parseInt(val)) {
                    this.value(parseInt(val));
                }
            }

            if (val > this.maxValue) {
                this.value(this.maxValue);
            } else if (val < this.minValue) {
                this.value(this.minValue);
            }
        }
    });
});
