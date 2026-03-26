define([
    "jquery",
    "mage/translate",
    "Magento_Ui/js/lib/knockout/bindings/range",
    "Magento_Ui/js/modal/modal"
], function ($, $t) {

    $.widget('mage.nearestStore', {
        options: {},
        url: null,
        useBrowserLocation: null,
        useGeo: null,
        imageLocations: null,
        map: {},
        marker: {},
        storeListIdentifier: '',
        mapId: '',
        mapContainerId: '',
        needGoTo: false,
        markerCluster: {},
        bounds: {},
        hiddenState: '-hidden',
        latitude: 0,
        longitude: 0,

        _create: function () {
            this.navigateMe();
        },

        navigateMe: function () {
            var self = this;
            self.needGoTo = 1;
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    self.latitude = position.coords.latitude;
                    self.longitude = position.coords.longitude;
                    self.makeAjaxCall(1);
                }, this.navigateFail.bind(self));
            } else {
            }
        },
        collectParams: function (sortByDistance, isReset) {
            return {
                'lat': this.latitude,
                'lng': this.longitude,
                'radius': this.getRadius(isReset),
                'product': this.options.productId,
                'category': this.options.categoryId,
                'sortByDistance': sortByDistance
            };
        },
        getRadius: function (isReset) {
            return null;
        },
        navigateFail: function (error) {
            // error param exists when user block browser location
            if (this.options.useGeoConfig == 1) {
                this.makeAjaxCall(1);
            } else if (error.code == 1) {
            }
        },
        makeAjaxCall: function (sortByDistance, isReset) {
            var self = this,
                params = this.collectParams(sortByDistance, isReset);

            $.ajax({
                url: self.options.ajaxCallUrl,
                type: 'POST',
                data: params,
                showLoader: true
            }).done($.proxy(function (response) {
                response = JSON.parse(response);
                self.options.jsonLocations = response;
                if(response.items.length){
                    var storeDetails = response.items[0];
                    var address = storeDetails.address + ', ' + storeDetails.city + ', '+ storeDetails.state+' ' + storeDetails.zip + ' ' + storeDetails.country;
                    $('.page-footer .footer-store-information .name').text(storeDetails.name);
                    $('.page-footer .footer-store-information .info .address').text(address);
                    $('.page-footer .footer-store-information .info .tel').text(storeDetails.phone);
                }
            }));
        },
    });

    return $.mage.nearestStore;
});
