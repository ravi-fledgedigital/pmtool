/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
define([
    'Magento_Ui/js/grid/filters/filters'
], function (Filters) {
    'use strict';

    return Filters.extend({
        initialize: function () {
            this._super();
            this.urlPathParts = window.location.pathname.split('/');
        },

        isFilterVisible: function (filter) {
            if (this.urlPathParts.includes(filter.inputName)) {
                return false;
            }

            return this._super();
        }
    });
});
