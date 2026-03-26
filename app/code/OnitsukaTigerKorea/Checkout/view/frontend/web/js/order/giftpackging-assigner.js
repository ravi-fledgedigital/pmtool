define([
    'jquery'
], function ($) {
    'use strict';

    return function (paymentData) {
        if (paymentData['extension_attributes'] === undefined) {
            paymentData['extension_attributes'] = {};
        }
        if($('[name="custom_attributes[is_gift]"]').is(':checked')) {
            paymentData['extension_attributes']['is_gift'] =  1;
        } else {
            paymentData['extension_attributes']['is_gift'] =  0;
        }
    };
});