/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define([
    'Magento_Ui/js/form/element/single-checkbox',
    'uiRegistry'
], function (SingleCheckbox, reg) {
    'use strict';

    return SingleCheckbox.extend({
        defaults: {
            valueMap: {
                'true': '1',
                'false': '0'
            },
            prefer: 'toggle',
            isShown: false,
            inverseVisibility: false,
            visible:false
        },
        toggleVisibility: function (selected) {
            this.isShown = selected in this.valuesForOptions;
            this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
        },
    });
});
