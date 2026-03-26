define([
    'jquery',
    'underscore',
    'jquery/ui',
    'mage/validation',
    'domReady!'
], function ($, _) {
    'use strict';

    $.widget('customer.citiesUpdater', {
        options: {
            isRegionRequired: true,
            citiesListId: '#city',
            districtListId: '#district'
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
                self.updateCity($(this).val());
            });
        },

        updateCity: function (regionId) {
            var self = this;
            var citySelector = $(self.options.citiesListId);
            var districtSelector = $(self.options.districtListId);
            var citiesList = self.options.jsonConfig.cities[regionId];

            citySelector.children('option:not(:first)').remove();
            districtSelector.children('option:not(:first)').remove();

            if (regionId > 0) {
                $.each(citiesList, function (k, v) {
                    citySelector.append(new Option(v.label, v.value));
                });
            }
        }
    });

    return $.customer.citiesUpdater;
});
