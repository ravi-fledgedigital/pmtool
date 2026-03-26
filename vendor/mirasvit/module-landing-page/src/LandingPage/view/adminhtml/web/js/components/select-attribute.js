define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'mage/url'
], function ($, Select, uiRegistry, url) {
    var self,
        optionValues = [],
        elements     = [];
    var value;

    function updateAttribute() {

        for (let i = 0; i < Object.keys(elements).length; i++) {

            if (elements[i].value() !== optionValues[i]) {
                self = elements[i];
                self.updateValueOptions();
                optionValues[i] = self.value();
            }
        }
    }

    return Select.extend({
        initialize: function () {
            self = this;
            this._super();

            self.updateValueOptions();

            elements.push(self);
            optionValues.push(self.value())

            self.value.subscribe(function () {
                updateAttribute();
            });

            return this;
        },


        updateValueOptions: function () {
            const FRONT_NAME = 'mst_landing/';
            let parentScope = self.parentScope,
                index       = parentScope.replace('data.filters.', '');
            if (self.value()) {
                // excluding secret key from base url
                const CURRENT_URL = BASE_URL.substring(0, BASE_URL.indexOf(FRONT_NAME)) + FRONT_NAME;
                
                url.setBaseUrl(CURRENT_URL);
                $.ajax({
                    url:        url.build('page/options'),
                    type:       'post',
                    dataType:   'json',
                    cache:      false,
                    showLoader: true,
                    data:       {attributeId: self.value()}
                }).done(function (response) {
                    if (!response.error) {
                        uiRegistry.get('mst_landing_form.mst_landing_form.general.products.filters.' + index + '.options').options(response.options);
                        value = uiRegistry.get('mst_landing_form.mst_landing_form.general.products.filters.' + index + '.options').initialValue;
                        uiRegistry.get('mst_landing_form.mst_landing_form.general.products.filters.' + index + '.options').value(value);
                    }
                });
            } else {
                uiRegistry.get('mst_landing_form.mst_landing_form.general.products.filters.' + index + '.options').options([]);
            }
        }
    });
});
