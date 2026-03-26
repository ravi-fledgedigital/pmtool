define([
    'jquery'
], function ($) {
    'use strict';

    return function (paymentData) {

        if (paymentData['extension_attributes'] === undefined) {
            paymentData['extension_attributes'] = {};
        }
        paymentData['extension_attributes']['purchaser_name'] =  $('[name="purchaser_name[purchaser_name]"]').val();
        paymentData['extension_attributes']['company_tax_code'] =  $('[name="company_tax_code[company_tax_code]"]').val();
        /*paymentData['extension_attributes']['company_name'] =  $('[name="company_name[company_name]"]').val();
        paymentData['extension_attributes']['customer_address'] =  $('[name="customer_address[customer_address]"]').val();
        paymentData['extension_attributes']['company_email_address'] =  $('[name="company_email_address[company_email_address]"]').val();
        paymentData['extension_attributes']['company_phone_number'] =  $('[name="company_phone_number[company_phone_number]"]').val();*/

    };
});
