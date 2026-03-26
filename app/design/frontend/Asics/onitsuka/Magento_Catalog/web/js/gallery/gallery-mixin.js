define([
    'jquery',
    'underscore'
], function ($,_) {
    'use strict';

    return function (gallery) {
        return gallery.extend({
            initialize: function (config, element) {

                var interval = setInterval(function () {
                    $('.fotorama_vertical_ratio > .fotorama__img').each(function (index) {
                        if (index === 0) {
                            $(this).attr('fetchpriority', 'high');
                        } else {
                            $(this).attr('fetchpriority', 'low');
                        }
                    });
                }, 500);

                setTimeout(function () {
                    clearInterval(interval);
                }, 5000);

                if (_.isUndefined(config) || _.isEmpty(config))
                    return this._super(config, element);

                if (_.isUndefined(config.data) || _.isEmpty(config.data))
                    return this._super(config, element);

                let wdpr = window.devicePixelRatio;

                _.each(config.data, function (imageObject) {

                    if (_.isUndefined(imageObject.fastly_srcset))
                        return;

                    if (!_.has(imageObject.fastly_srcset, wdpr))
                        return;

                    imageObject.img = imageObject.fastly_srcset[wdpr];
                });

                this._super(config, element);
            }
        });
    };
});
