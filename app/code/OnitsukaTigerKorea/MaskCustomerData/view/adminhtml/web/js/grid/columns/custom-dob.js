define([
    'Magento_Ui/js/grid/columns/date',
    'moment'
], function (DateColumn, moment) {
    'use strict';

    return DateColumn.extend({
        defaults: {
            bodyTmpl: 'OnitsukaTigerKorea_MaskCustomerData/grid/cells/custom-dob'
        },

        /**
         * Custom Formatter (Optional)
         */
        getLabel: function (value) {
            if (value[this.index] && value.website_id[0] === "4") {
                return moment(value[this.index]).format("MMM **, YYYY");
            } else if (!value[this.index]) {
                return '';
            }
            return moment(value[this.index]).format("MMM DD, YYYY");
        }
    });
});
