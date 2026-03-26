/*jshint browser:true jquery:true*/
define(
    [
        'jquery',
        'uiRegistry',
        'underscore',
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/step-navigator'
    ],
    function($, registry, _, Component, quote, stepNavigator) {
        'use strict';

        return Component.extend({
            defaults: {
                deps : 'onitsukatigerCheckoutProvider',
                template: 'OnitsukaTiger_OrderAttribute/order-attributes-information',
                hideEmpty: true,
                collectPlaces: []
            },

            isVisible: function() {
                return !quote.isVirtual() && this.isPaymentStepVisible();
            },
            isPaymentStepVisible: function () {
                var steps = stepNavigator.steps();

                if (!_.isUndefined(_.where(steps, {'code' : 'payment'})[0])) {
                    return _.where(steps, {'code' : 'payment'})[0].isVisible();
                }

                return false;
            },

            getOrderAttributes: function () {
                var attributes = [],
                    item;

                _.each(this.collectPlaces, function(place) {
                    var container = registry.filter('index = ' + place);

                    if (container.length) {
                        _.each(container[0].elems(), function(elem) {
                            if (elem.visible()) {
                                item = this.getAttributeDataFromElement(elem);
                                if (item) {
                                    attributes.push(item);
                                }
                            }
                        }.bind(this) );
                    }
                }.bind(this));

                return attributes;
            },

            getAttributeDataFromElement: function (elem) {
                var item = { label: elem.label },
                    elemValue = typeof elem.getPreviewValue === 'function'
                        ? elem.getPreviewValue()
                        : elem.value();

                if (!this.hideEmpty || (this.hideEmpty && !_.isEmpty(elemValue))) {
                    item['value'] = elemValue;

                    return item;
                } else {
                    return false;
                }
            }
        });
    }
);
