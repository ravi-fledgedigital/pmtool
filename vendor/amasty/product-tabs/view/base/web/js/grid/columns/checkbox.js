define([
    'underscore',
    'mage/translate',
    'Magento_Ui/js/grid/columns/column',
    'jquery'
], function (_, $t, Column, jQuery) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Amasty_CustomTabs/grid/cells/checkbox',
            draggable: false,
            sortable: false
        },

        /**
         * Remove disable class from Insert Attribute button after Attribute has been chosen.
         *
         * @return {Boolean}
         */
        selectAttribute: function () {
            if (jQuery('#insert_attribute').hasClass('disabled')) {
                jQuery('#insert_attribute').removeClass('disabled');
            }

            return true;
        }
    });
});
