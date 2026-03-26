define([
    'jquery',
    'underscore',
    'mage/utils/wrapper'
], function ($, _, wrapper) {
    'use strict';

    return function (target) {
        return target.extend({
            initialize: function () {
                this._super();
                $(document).on('change', '#full_agreement', function () {
                    let isChecked = $(this).prop('checked');
                    var allKakaoCheckBox = $(this).closest('form').find(':checkbox')
                        .not('#billing-address-same-as-shipping-kakaopay')
                        .not('#billing-address-same-as-shipping-cashondelivery');
                    allKakaoCheckBox.prop('checked', isChecked);
                });
                return this;
            }
        });
    };
});