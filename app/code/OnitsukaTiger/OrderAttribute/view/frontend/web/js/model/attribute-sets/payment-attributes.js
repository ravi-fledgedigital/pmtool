define([
    'Magento_Checkout/js/model/quote'
], function (quote) {
    'use strict';

    var attributesTypes = [
            'onitsukatigerShippingAttributes',
            'onitsukatigerPaymentAttributes',
            'onitsukatigerSummaryAttributes',
            'onitsukatigerShippingMethodAttributes',
            'before-place-order.onitsukatigerPaymentMethodAttributes'
        ],
        formCode = 'onitsukatiger_checkout';

    if (quote.isVirtual()) {
        attributesTypes = [
            'onitsukatigerPaymentAttributes',
            'before-place-order.onitsukatigerPaymentMethodAttributes',
            'onitsukatigerSummaryAttributes'
        ];
        formCode = 'onitsukatiger_checkout_virtual';
    }

    return {
        'attributeTypes': attributesTypes,
        'formCode': formCode
    }
});
