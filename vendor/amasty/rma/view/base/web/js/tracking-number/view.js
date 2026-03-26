define([
    'uiElement',
    'underscore',
    'jquery',
    'Amasty_Rma/js/modal/alert/ammodal-popup',
    'mage/translate'
], function (Element, _, $, message) {
    'use strict';

    return Element.extend({
        defaults: {
            template: 'Amasty_Rma/tracking-number/view',
            trackingNumbers: [],
            carriers: null,
            carrier: null,
            addAllowed: true,
            saveUrl: null,
            removeUrl: null,
            indexedCarriers: {}
        },
        initialize: function () {
            this._super();

            _.each(this.carriers, function (val) {
                this.indexedCarriers[val.code] = val.label;
            }.bind(this));

            return this;
        },
        initObservable: function () {
            this._super().observe([
                'value',
                'carrier',
                'trackingNumbers'
            ]);

            return this;
        },
        initLinks: function () {
            this._super();
            if (!_.isUndefined(this.urlhash)) {
                this.removeUrl += 'hash/' + this.urlhash;
                this.saveUrl += 'hash/' + this.urlhash;
            }

            return this;
        },
        addTracking: function () {
            if (!this.carrier() || !this.value()) {
                message({'content': $.mage.__('Carrier wasn\'t selected or tracking number wasn\'t filled.')});
                return;
            }
            $.ajax({
                url: this.saveUrl,
                data: { 'code': this.carrier(), 'number': this.value()},
                method: 'post',
                global: false,
                dataType: 'json',
                success: function (data) {
                    if (!_.isUndefined(data.success)) {
                        var numbers = this.trackingNumbers();
                        //TODO validate
                        numbers.push(
                            {
                                'id': data.id,
                                'code': this.carrier(),
                                'number': this.value(),
                                'customer': 1
                            }
                        );
                        this.trackingNumbers(numbers);
                        this.carrier("");
                        this.value("");
                    }
                }.bind(this)
            });

            return this;
        },
        removeTracking: function (id) {
            this.trackingNumbers(
                _.reject(this.trackingNumbers(), function (tracking) {
                    return tracking.id == id;
                })
            );
            $.ajax({
                url: this.removeUrl,
                data: { 'id': id},
                method: 'post',
                global: false,
                dataType: 'json'
            });

            return this;
        }
    });
});
