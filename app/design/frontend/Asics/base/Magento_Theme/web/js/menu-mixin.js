define(['jquery'], function($) {
    'use strict';
    return function(widget) {
        $.widget('mage.menu', $.mage.menu, {
            _toggleMobileMode: function () {
                var subMenus;

                $(this.element).off('mouseenter mouseleave');

                subMenus = this.element.find('li.parent');
                $.each(subMenus, $.proxy(function (index, item) {
                    var category = $(item).find('> a span').not('.ui-menu-icon').text(),
                        categoryUrl = $(item).find('> a').attr('href'),
                        menu = $(item).find('> .ui-menu');
                    if($(item).parent().children('.all-category').length) {
                        category = $(item).parent().children('.all-category').find('a').text() + " / "+ category
                    }
                    this.categoryLink = $('<a>')
                        .attr('href', 'javascript:void(0)')
                        .text($.mage.__('%1').replace('%1', category));

                    this.categoryParent = $('<li>')
                        .addClass('ui-menu-item all-category')
                        .html(this.categoryLink);

                    if (menu.find('.all-category').length === 0) {
                        menu.prepend(this.categoryParent);
                    }

                }, this));
                $('li.parent').removeClass('level-top').addClass('level-top');
                $('li.level-top.parent a.ui-corner-all').click(function(e){
                    if($(this).next().length){
                        $(this).next().toggleClass('active-show');
                        $(this).toggleClass('ui-state-active-content');
                        $(this).parent().toggleClass('ui-state-active-content');
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
        return $.mage.menu;
    }
});
