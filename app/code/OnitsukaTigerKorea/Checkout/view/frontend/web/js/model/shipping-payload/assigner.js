define([
    'jquery',
    'underscore',
], function ($,_, registry) {
    'use strict';

    return function (container) {
        var is_gift = $('[name="custom_attributes[is_gift]"]').is(':checked');

        container.extension_attributes = _.extend(
            container.extension_attributes || {},
            {
                is_gift: is_gift
            }
        );
    };
});
