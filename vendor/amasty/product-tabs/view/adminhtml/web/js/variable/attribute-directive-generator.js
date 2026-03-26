define([
    'underscore'
], function (_) {
    'use strict';

    return _.extend({
        directiveTemplate: '{{amcustomtabs_attribute code="%s"}}',

        /**
         * @param {String} path
         * @return {String}
         */
        processConfig: function (path) {
            return this.directiveTemplate.replace('%s', path);

        }

    });
});
