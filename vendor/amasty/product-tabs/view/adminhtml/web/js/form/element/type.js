define([
    'Magento_Ui/js/form/element/select'
], function (select) {
    return select.extend({
        getOption: function () {
            var option = this._super(this.source.data.type),
                newOption = {
                    'value': option.value,
                    'label': option.label
                };
            if (this.source.data.type == 2) {
                newOption.label += ' ' + this.source.data.module_name;
            }

            return newOption;
        }
    });
});
