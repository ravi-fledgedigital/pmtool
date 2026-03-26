define([
    'jquery',
    'moment',
    'jquery/validate',
    'mage/translate'
], function ($, moment) {
    'use strict';

    $.validator.addMethod(
        'validate-dob-custom',
        function (value ,element) {
            if (value === '') {
                return true;
            }
            if(typeof(jQuery(element).data('validate')['validate-date-birth']) !='undefined'){
                if(typeof(jQuery(element).data('validate')['validate-date-birth']['dateFormat']) !='undefined'){
                    var dateFormat = jQuery(element).data('validate')['validate-date-birth']['dateFormat'].toLowerCase();
                    if(dateFormat == 'd/m/y' || dateFormat == 'd/mm/y' || dateFormat == 'dd/m/y' || dateFormat == 'dd/mm/y' ){
                        var valueTpm = value.split('/');
                        value = valueTpm[1]+'/'+valueTpm[0]+'/'+valueTpm[2];
                    }
                    if(dateFormat == 'y/m/d'){
                        var valueTpm = value.split('/');
                        value = valueTpm[1]+'/'+valueTpm[2]+'/'+valueTpm[0];
                    }
                    if(dateFormat == 'y/d/m' ){
                        var valueTpm = value.split('/');
                        value = valueTpm[2]+'/'+valueTpm[1]+'/'+valueTpm[0];
                    }
                }
            }
            return moment(value).isBefore(moment());
        },
        $.mage.__('The Date of Birth should not be greater than today.')
    );
    $.validator.addMethod(
        'validate-date-birth',
        function (value ,element) {
            if (value === '') {
                return true;
            }
            if(typeof(jQuery(element).data('validate')['validate-date-birth']) !='undefined'){
                if(typeof(jQuery(element).data('validate')['validate-date-birth']['dateFormat']) !='undefined'){
                    var dateFormat = jQuery(element).data('validate')['validate-date-birth']['dateFormat'].toLowerCase();
                    if(dateFormat == 'd/m/y' || dateFormat == 'd/mm/y' || dateFormat == 'dd/m/y' || dateFormat == 'dd/mm/y' ){
                        var valueTpm = value.split('/');
                        value = valueTpm[1]+'/'+valueTpm[0]+'/'+valueTpm[2];
                    }
                    if(dateFormat == 'y/m/d'){
                        var valueTpm = value.split('/');
                        value = valueTpm[1]+'/'+valueTpm[2]+'/'+valueTpm[0];
                    }
                    if(dateFormat == 'y/d/m' ){
                        var valueTpm = value.split('/');
                        value = valueTpm[2]+'/'+valueTpm[1]+'/'+valueTpm[0];
                    }
                }
            }
            var test = moment(value);
            return $.mage.isEmptyNoTrim(value) || test.isValid();
        },
        $.mage.__('Please enter a valid date.')
    );
});
