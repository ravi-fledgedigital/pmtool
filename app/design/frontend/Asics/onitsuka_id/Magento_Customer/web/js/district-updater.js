define([
    'jquery',
    'underscore',
    'jquery/ui',
    'mage/validation',
    'domReady!'
], function ($, _) {
    'use strict';

    $.widget('customer.districtUpdater', {
        options: {
            districtListId: '#district',
            defaultDistrict: '',
        },

        /**
         *
         * @private
         */
        _create: function () {
            var self = this;
            self._bind();
        },

        _bind: function () {
            var self = this;

            self.element.on('change', function () {
                self.updateDistrict($(this).val());
            });
        },

        updateDistrict: function (defaultCity) {
            var self = this,
                districtListId = $(self.options.districtListId),
                districtList = self.options.jsonConfig.districts[defaultCity];

            if (defaultCity && defaultCity.length > 0) {
                districtListId.children('option:not(:first)').remove();

                $.each(districtList, function (k, v) {
                    districtListId.append(new Option(v.label, v.value));
                });
            }
        }
    });

    return $.customer.districtUpdater;
});
