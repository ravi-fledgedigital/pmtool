define([
    'uiRegistry'
], function (registry) {
    'use strict';

    return function (attributesTypes) {
        var onitsukatigerCheckoutProvider = registry.get('onitsukatigerCheckoutProvider'),
            focused = false,
            result = {};

        for (var key in attributesTypes) {
            if (attributesTypes.hasOwnProperty(key)) {
                result = _.extend(result, onitsukatigerCheckoutProvider.get(attributesTypes[key]));
                onitsukatigerCheckoutProvider.set('params.invalid', false);

                var customScope = attributesTypes[key];
                if (customScope.indexOf('.') !== -1) {
                    customScope = customScope.substr(customScope.indexOf('.') + 1);
                }
                onitsukatigerCheckoutProvider.trigger(customScope + '.data.validate');

                if (onitsukatigerCheckoutProvider.get('params.invalid') && !focused) {
                    var container = registry.filter("index = " + attributesTypes[key] + 'Container');
                    if (container.length) {
                        container[0].focusInvalidField();
                    }
                    focused = true;
                    onitsukatigerCheckoutProvider.set('params.invalid', false);
                }
            }
        }

        if (focused) {
            onitsukatigerCheckoutProvider.set('params.invalid', true);
        }

        if (onitsukatigerCheckoutProvider.get('params.invalid')) {
            return false;
        } else {
            return result;
        }
    }
});
