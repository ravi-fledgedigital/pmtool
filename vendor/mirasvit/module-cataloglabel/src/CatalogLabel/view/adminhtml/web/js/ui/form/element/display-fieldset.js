define([
    'Magento_Ui/js/form/components/fieldset'
], function (Fieldset) {
    return Fieldset.extend({
        initObservable: function () {
            this._super();

            this.changed.subscribe(this.handlePlaceholderValidate.bind(this));

            return this;
        },

        handlePlaceholderValidate: function () {
            let childrenHaveValues = false;
            let placeholderElem    = null;

            this._elems.forEach(function (elem) {
                if (elem.index == 'delete') {
                    return;
                }

                if (elem.index == 'placeholder_id') {
                    placeholderElem = elem;
                    return;
                }

                let value = elem.value();

                if (!value || (Array.isArray(value) && !value.length)) {
                    return;
                }

                childrenHaveValues = true;
            });

            if (childrenHaveValues) {
                placeholderElem.validation = {'required-entry': true};
            } else {
                placeholderElem.validation = {};
            }
        }
    })
});
