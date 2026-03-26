define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';
    return function(){
        if (window.innerWidth < 748) {
            return;
        }
        const filtersOverflowOffset = 20;
    
        const sidebarMain = $('.sidebar.sidebar-main');
        sidebarMain.addClass('mst-sticky-sidebar');
        const filterContent = sidebarMain.find('.block-content.filter-content');
        const filterOptions = sidebarMain.find('.filter-options');
    
        if (!filterContent.length && !filterOptions.length) {
            return;
        }
    
        var maxHeight = filterContent.height() - filterOptions.height() + filtersOverflowOffset;
        filterOptions.css('maxHeight', 'calc(100vh - ' + maxHeight + 'px)');
    }
});