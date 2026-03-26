define([
    'jquery',
    'ko',
    'underscore',
    'mageUtils',
    'Magento_Ui/js/form/element/abstract',
    'OnitsukaTiger_OrderAttribute/js/form/relationAbstract',
    'Magento_Customer/js/model/customer',
    'Magento_Ui/js/lib/validation/validator',
], function ($, ko, _, utils, Abstract, relationAbstract, customer, validator) {
    'use strict';

    // relationAbstract - attribute dependencies
    return Abstract.extend(relationAbstract).extend({
        setInitialValue: function () {
            this.initialValue = this.getInitialValue()
            if(this.index =='tax_id'){
                this.placeholder = "Tax Id";
                this.initialValue = customerData.taxvat;
            }


            if (this.value.peek() !== this.initialValue) {
                this.value(this.initialValue);
            }

            this.on('value', this.onUpdate.bind(this));
            this.isUseDefault(this.disabled());

            return this;
        },
        onUpdate: function () {
            this.bubble('update', this.hasChanged());

            this.validateChange();
            if(this.index =='tax_id'){
                $('.opc-wrapper .fieldset.address .field[name$=".vat_id"] input').val(this.value());
            }
        },
        validateChange: function () {
            if(Object.keys(this.validation)['required-entry']){
                var value = this.value(),
                    result = validator(this.validation, value, this.validationParams),
                    message = !this.disabled() && this.visible() ? result.message : '',
                    isValid = this.disabled() || !this.visible() || result.passed;

                this.error(message);
                this.error.valueHasMutated();
                this.bubble('error', message);

                //TODO: Implement proper result propagation for form
                if (this.source && !isValid) {
                    this.source.set('params.invalid', true);
                }
                return {
                    valid: isValid,
                    target: this
                };
            }
        },
        validate: function () {
            if(Object.keys(this.validation)['required-entry']) {
                var value = this.value(),
                    result = validator(this.validation, value, this.validationParams),
                    message = !this.disabled() && this.visible() ? result.message : '',
                    isValid = this.disabled() || !this.visible() || result.passed;

                this.error(message);
                this.error.valueHasMutated();
                this.bubble('error', message);

                //TODO: Implement proper result propagation for form
                if (this.source && !isValid) {
                    this.source.set('params.invalid', true);
                }
                if (this.index == 'tax_id') {
                    if (isValid && window.checkoutConfig.isCustomerLoggedIn) {
                        var formData = new FormData();
                        formData.append("form_key", $('.columns .column.main input[name="form_key"]').val());
                        formData.append("customer_id", window.checkoutConfig.customerData.id);
                        formData.append("tax_id", this.value());
                        $.ajax({
                            url: $('#checkout').data('taxid'),
                            data: formData,
                            type: 'post',
                            dataType: 'json',
                            cache: false,
                            contentType: false,
                            processData: false,
                            success: function (res) {
                            },
                            /** @inheritdoc */
                            error: function (res) {
                            }
                        });
                    }
                }
                return {
                    valid: isValid,
                    target: this
                };
            }
        },
        isFieldInvalid: function () {
            return this.error() && this.error().length ? this : null;
        }
    });
});
