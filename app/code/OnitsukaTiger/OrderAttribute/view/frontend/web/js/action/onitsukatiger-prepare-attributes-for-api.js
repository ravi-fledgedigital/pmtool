define([
    'Magento_Checkout/js/model/quote'
    ], function (quote) {
        'use strict';

        return function (result, checkoutFormCode) {
            var apiResult = {
                'onitsukatigerCartId' : quote.getQuoteId(),
                'checkoutFormCode' : checkoutFormCode,
                'shippingMethodCode' : '',
                'entityData': {
                    'custom_attributes': []
                }
            }, fileValue;

            if (!quote.isVirtual()) {
                var rate = quote.shippingMethod();

                if (rate && rate.carrier_code && rate.method_code) {
                    apiResult.shippingMethodCode = rate.carrier_code + '_' + rate.method_code;
                } else {
                    apiResult.shippingMethodCode = 'unknown';
                }
            }

            _.each(result, function(value, code) {
                if (_.isArray(value)) {
                    fileValue = _.first(value);
                    if (!_.isEmpty(fileValue) && _.isObject(fileValue)) {
                        value = fileValue.file;
                    } else {
                        value = value.join(',');
                    }
                }
                apiResult.entityData.custom_attributes.push(
                    {
                        'attribute_code' : code,
                        'value' : value
                    }
                );
            });

            return apiResult;
        }
    }
);
