define(
    [
        'jquery',
        'ko',
        'Magento_Ui/js/form/form',
        'underscore',
        'OnitsukaTiger_OrderAttribute/js/checkout-data',
        'uiRegistry',
        'OnitsukaTiger_OrderAttribute/js/action/observe-shipping-method',
        'OnitsukaTiger_OrderAttribute/js/form/relationRegistry'
    ],
    function (
        $,
        ko,
        Component,
        _,
        checkoutData,
        registry,
        observeShippingMethod,
        relationRegistry
    ) {
        return Component.extend({
            isVisible: ko.observable(false),

            relationRegistry: false,

            dependsToShow: [],

            initialize: function () {
                var self = this;
                this._super();

                if (!_.isUndefined(this.fields)) {
                    var fieldsetPath = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset';

                    _.each(this.fields, function (value) {
                        registry.async(fieldsetPath + '.' + value)(function (element) {
                            new observeShippingMethod(element).observeShippingMethods();
                        });
                    });
                }

                this.relationRegistry = relationRegistry;
                this.relationRegistry.clear();
                registry.async('onitsukatigerCheckoutProvider')(function (checkoutProvider) {
                    var scopeCheckoutData = checkoutData.getCheckoutData(self.amScope);

                    if (scopeCheckoutData ) {
                        checkoutProvider.set(
                            self.amScope,
                            $.extend(true, {}, checkoutProvider.get(self.amScope), scopeCheckoutData )
                        );
                    }
                    checkoutProvider.on(self.amScope, function (scopeData) {
                        checkoutData.setCheckoutData(self.amScope, scopeData);
                    });
                });
            },

            initElement: function (element) {
                this._super();
                new observeShippingMethod(element).observeShippingMethods();
            },
            /**
             * Magento 2.1.1 focusInvalid nonexistent fix
             */
            focusInvalidField: function () {
                var invalidField = _.find(this.delegate('isFieldInvalid'));

                if (!_.isUndefined(invalidField) && _.isFunction(invalidField.focused)) {
                    invalidField.focused(true);
                }

                return this;
            }
        });
    }
);
