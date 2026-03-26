define(['jquery'], function ($) {
    'use strict';

    $.widget('mst.navThemeCompatibility', {
        _create: function () {
            this.init()

            $(document).on('contentUpdated', function () {
                this.init()
            }.bind(this))
        },

        init: function () {
            $('dd.filter-options-content').hide();

            $('.mst-nav__theme-magento-blank .sidebar.sidebar-main dd.filter-options-content').each(function(i, filter){
                if (this.options.active.includes(i)) {
                    $(filter).show();
                }
            }.bind(this))

            //toggle filter by click on title
            this.toggleFilter = this.toggleFilter ?? function toggleFilter(e) {
                $(e.target).next('dd.filter-options-content').toggle()
            }

            $('.filter dt.filter-options-title')
                .off('click', this.toggleFilter)
                .on('click', this.toggleFilter)
        }
    });

    return $.mst.navThemeCompatibility;
});
