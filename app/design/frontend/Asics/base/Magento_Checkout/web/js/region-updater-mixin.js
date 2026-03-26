/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/template',
    'underscore',
    'jquery-ui-modules/widget',
    'mage/validation'
], function ($, mageTemplate, _) {
    'use strict';
    var mixin = {
        _create: function () {
            this._initCountryElement();

            this.currentRegionOption = this.options.currentRegion;
            this.regionTmpl = mageTemplate(this.options.regionTemplate);
            if(this.element.find('option:selected').length){
                this._updateRegion(this.element.find('option:selected').val());
            }

            $(this.options.regionListId).on('change', $.proxy(function (e) {
                this.setOption = false;
                this.currentRegionOption = $(e.target).val();
                this.element.parents('form').find('#city').val('');
            }, this));

            $(this.options.regionInputId).on('focusout', $.proxy(function () {
                this.setOption = true;
            }, this));
        }
    };

    return function (target) {
        $.widget('mage.regionUpdater', target, mixin);
        return $.mage.regionUpdater;
    };
});
