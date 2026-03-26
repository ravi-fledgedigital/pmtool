/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @api
 */
define([
    'ko',
    'jquery'
], function (ko, $) {
    'use strict';

    var storeId = ko.observable(null);

    return {
        /**
         *
         * @param latitude
         * @param longitude
         * @param zoom
         */
        getMap: function (latitude,longitude, zoom) {
            var mapLatLng = new google.maps.LatLng(latitude, longitude),
                mapOptions = {
                    zoom: zoom,
                    center: mapLatLng,
                },
                map = new google.maps.Map(document.getElementById("googleMap"), mapOptions),
                marker = new google.maps.Marker({
                    position: mapLatLng,
                    animation: google.maps.Animation.DROP
                });

            marker.setMap(map);
        },
        setFieldAddress: function (item) {
            var jsonRegion = checkoutConfig.pickup_config.jsonRegion;
            $('.form-shipping-address').find('input[name="street[0]"]').val(item.address1).trigger('change');
            $('.form-shipping-address').find('input[name="street[1]"]').val(item.address2).trigger('change');
            $('.form-shipping-address').find('input[name="region"]').val(item.state).trigger('change');
            $('.form-shipping-address').find('select[name="country_id"]').val(window.defaultCountry).trigger('change');
            $('.form-shipping-address').find('select[name="region_id"]').val(jsonRegion[item.state]).trigger('change');
            $('.form-shipping-address').find('select[name="city"]').val(item.city).trigger('change');
            $('.form-shipping-address').find('select[name="custom_attributes[district]"]').val(item.district).trigger('change');
            $('.form-shipping-address').find('input[name="postcode"]').val(item.zipCode).trigger('change');
        },
        /**
         *
         * @returns {*}
         */
        getStoreId: function () {
            return this.storeId();
        },
        /**
         * Selected Pickup Store ID
         */
        storeId: storeId,
    };
});
