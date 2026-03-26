define([
    'underscore'
], function (_) {
    'use strict';

    /**
     * @abstract
     */
    return {
        dependsToShow: [],

        clear: function () {
            this.dependsToShow = [];
        },

        add: function (dependIndex, parentIndex) {
            if (_.isUndefined(this.dependsToShow[dependIndex])) {
                this.dependsToShow[dependIndex] = [];
            }
            if (!_.contains(this.dependsToShow[dependIndex], parentIndex)) {
                this.dependsToShow[dependIndex].push(parentIndex);
            }
        },

        remove: function (dependIndex, parentIndex) {
            if (!_.isUndefined(this.dependsToShow[dependIndex])) {
                if (_.contains(this.dependsToShow[dependIndex], parentIndex)) {
                    this.dependsToShow[dependIndex].splice(this.dependsToShow[dependIndex].indexOf(parentIndex), 1);
                }
                if (!this.dependsToShow[dependIndex].length) {
                    delete(this.dependsToShow[dependIndex]);
                }
            }
        },

        isExist: function (dependIndex) {
            return !_.isUndefined(this.dependsToShow[dependIndex]);
        }
    };
});