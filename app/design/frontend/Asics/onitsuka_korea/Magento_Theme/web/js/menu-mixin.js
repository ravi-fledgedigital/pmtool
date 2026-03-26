define(['jquery'], function($) {
    'use strict';
    return function(widget) {
        $.widget('mage.menu', $.mage.menu, {
            _toggleMobileMode: function () {
                var subMenus;
                // Remove desktop hover handlers
                $(this.element).off('mouseenter mouseleave');
                // Ensure each parent has top/bottom "All Items" entries
                subMenus = this.element.find('li.parent');
                $.each(subMenus, $.proxy(function (index, item) {
                    var category = $(item).find('> a').text(),
                        categoryUrl = $(item).find('> a').attr('href'),
                        menu = $(item).find('> .ui-menu');
                    if ($(item).parent().children('.all-category').length) {
                        category = $(item).parent().children('.all-category').find('a').text() + " / " + category;
                    }
                    if (menu.length && menu.find('.all-category').length === 0) {
                        var $categoryLink = $('<a>').attr('href', categoryUrl).text($.mage.__('All Items'));
                        var $categoryParent = $('<li>').addClass('ui-menu-item all-category bottom').html($categoryLink);
                        var $categoryLinkFirst = $('<a>').attr('href', categoryUrl).text($.mage.__('All %1').replace('%1', category));
                        var $categoryParentFirst = $('<li>').addClass('ui-menu-item all-category top').html($categoryLinkFirst);
                        menu.append($categoryParent);
                        menu.prepend($categoryParentFirst);
                    }
                }, this));
                // keep level-top consistent
                $('li.parent').removeClass('level-top').addClass('level-top');
                // Namespaced delegated click handler that works for both 'a.ui-menu-item-wrapper' and direct '> a'
                $(document).off('click.mobileMenuToggle').on('click.mobileMenuToggle',
                    'li.level-top.parent a.ui-menu-item-wrapper, li.level-top.parent > a, li.parent > a.no-link-desktop, li.parent > a.no-link-desktop.ui-menu-item-wrapper',
                    function (e) {
                        var $anchor = $(this);
                        // robust find: submenu is the sibling ul.ui-menu (may not be the immediate next() in some DOM variants)
                        var $submenu = $anchor.siblings('ul.ui-menu, .ui-menu');
                        if ($submenu.length) {
                            // stop navigation and other handlers
                            e.preventDefault();
                            e.stopPropagation();
                            var $parentLi = $anchor.closest('li.parent');
                            // Close any sibling parent menus (only at the same level)
                            $parentLi.siblings('li.parent').each(function () {
                                var $sib = $(this);
                                if($parentLi.hasClass('level0')){
                                    $sib.find('> .ui-menu').removeClass('active-show');
                                    $sib.removeClass('ui-state-active-content');
                                }
                                $sib.find('> a, > .ui-menu-item-wrapper').removeClass('ui-state-active-content active-click-menu ui-state-active');

                            });
                            // Toggle the clicked submenu
                            $submenu.toggleClass('active-show');
                            // Toggle classes on the clickable anchor and parent li
                            $anchor.toggleClass('ui-state-active-content active-click-menu');
                            $parentLi.toggleClass('ui-state-active-content');
                            // Append navigation-store-links to top level submenu once
                            if ($parentLi.hasClass('level0')) {
                                if (!$submenu.find('.mobile-list-link-sub').length) {
                                    var $storeLinks = $('.page-wrapper .page-header .page-header-block-center .navigation-store-links').first();
                                    if ($storeLinks.length) {
                                        $submenu.append('<li class="mobile-list-link-sub">' + $storeLinks.prop('outerHTML') + '</li>');
                                    }
                                }
                            }
                            if ($parentLi.hasClass('level0')) {
                                $('.mobile-list-link-sub').hide();
                            }
                            return false;
                        }
                        // No submenu -> allow normal navigation
                        return true;
                    }
                );
                // Megamenu-mobile: keep natural navigation (optional: force redirect)
                $(document).off('click.megamenuMobile').on('click.megamenuMobile', 'nav.navigation li.level1[megamenu-mobile="true"] > a', function (e) {
                    // let normal navigation happen; uncomment below to force JS redirect instead
                    // e.preventDefault();
                    // window.location.href = $(this).attr('href');
                    // return false;
                });
            },
            /**
             * @private
             */
            _init: function () {
                this._super();
                if (this.options.expanded === true) {
                    this.isExpanded();
                }
                if (this.options.responsive === true) {
                    mediaCheck({
                        media: this.options.mediaBreakpoint,
                        entry: $.proxy(function () {
                            this._toggleMobileMode();
                        }, this),
                        exit: $.proxy(function () {
                            this._toggleDesktopMode();
                            $(".nav-sections .navigation > ul").on("hover",
                                function() {
                                    $('.page-header-swap').removeClass('active-menu').addClass('active-menu');
                                }, function() {
                                    $('.page-header-swap').removeClass('active-menu');
                                }
                            );
                        }, this)
                    });
                }
                this._assignControls()._listen();
                this._setActiveMenu();
            },
            /**
             * @private
             */
            _toggleDesktopMode: function () {
                var categoryParent, html;
                $(this.element).off('click mousedown mouseenter mouseleave');
                this._on({
                    /**
                     * Prevent focus from sticking to links inside menu after clicking
                     * them (focus should always stay on UL during navigation).
                     */
                    'mousedown .ui-menu-item > a': function (event) {
                        event.preventDefault();
                    },
                    /**
                     * Prevent focus from sticking to links inside menu after clicking
                     * them (focus should always stay on UL during navigation).
                     */
                    'click .ui-state-disabled > a': function (event) {
                        event.preventDefault();
                    },
                    /**
                     * @param {jQuer.Event} event
                     */
                    'click .ui-menu-item:has(a)': function (event) {
                        var target = $(event.target).closest('.ui-menu-item');
                        if (!this.mouseHandled && target.not('.ui-state-disabled').length) {
                            this.select(event);
                            // Only set the mouseHandled flag if the event will bubble, see #9469.
                            if (!event.isPropagationStopped()) {
                                this.mouseHandled = true;
                            }
                            // Open submenu on click
                            if (target.has('.ui-menu').length) {
                                this.expand(event);
                            } else if (!this.element.is(':focus') &&
                                $(this.document[0].activeElement).closest('.ui-menu').length
                            ) {
                                // Redirect focus to the menu
                                this.element.trigger('focus', [true]);
                                // If the active item is on the top level, let it stay active.
                                // Otherwise, blur the active item since it is no longer visible.
                                if (this.active && this.active.parents('.ui-menu').length === 1) { //eslint-disable-line
                                    clearTimeout(this.timer);
                                }
                            }
                        }
                    },
                    /**
                     * @param {jQuery.Event} event
                     */
                    'mouseenter .ui-menu-item': function (event) {
                        var target = $(event.currentTarget),
                            submenu = this.options.menus,
                            ulElement,
                            ulElementWidth,
                            width,
                            targetPageX,
                            rightBound;
                        if (target.has(submenu)) {
                            ulElement = target.find(submenu);
                            ulElementWidth = ulElement.outerWidth(true);
                            width = target.outerWidth() * 2;
                            targetPageX = target.offset().left;
                            rightBound = $(window).width();
                            if (ulElementWidth + width + targetPageX > rightBound) {
                                ulElement.addClass('submenu-reverse');
                            }
                            if (targetPageX - ulElementWidth < 0) {
                                ulElement.removeClass('submenu-reverse');
                            }
                        }
                        // Remove ui-state-active class from siblings of the newly focused menu item
                        // to avoid a jump caused by adjacent elements both having a class with a border
                        target.siblings().children('.ui-state-active').removeClass('ui-state-active');
                        this.focus(event, target);
                    },
                    /**
                     * @param {jQuery.Event} event
                     */
                    'mouseleave': function (event) {
                        this.collapseAll(event, true);
                    },
                    /**
                     * Mouse leave.
                     */
                    'mouseleave .ui-menu': 'collapseAll'
                });
                categoryParent = this.element.find('.all-category');
                html = $('html');
                var subMenus = this.element.find('li.parent.level1');
                $.each(subMenus, $.proxy(function (index, item) {
                    var category = $(item).find('> a').text(),
                        categoryUrl = $(item).find('> a').attr('href'),
                        menu = $(item).find('> .ui-menu');
                    if($(item).parent().children('.all-category').length) {
                        category = $(item).parent().children('.all-category').find('a').text() + " / "+ category
                    }
                    this.categoryLink = $('<a>')
                        .attr('href', categoryUrl)
                        .attr('alt', category)
                        .attr('title', category)
                        .text($.mage.__('All Items'));
                    this.categoryParent = $('<li>')
                        .addClass('ui-menu-item all-category bottom')
                        .html(this.categoryLink);
                    this.categoryLinkFirst = $('<a>')
                        .attr('href', categoryUrl)
                        .attr('alt', category)
                        .attr('title', category)
                        .text($.mage.__('All %1').replace('%1', category));
                    this.categoryParentFirst = $('<li>')
                        .addClass('ui-menu-item all-category top')
                        .html(this.categoryLinkFirst);
                    if (menu.find('.all-category').length === 0) {
                        menu.append(this.categoryParent);
                        menu.prepend(this.categoryParentFirst);
                    }
                }, this));
                // categoryParent.remove();
                if (html.hasClass('nav-open')) {
                    html.removeClass('nav-open');
                    setTimeout(function () {
                        html.removeClass('nav-before-open');
                    }, this.options.hideDelay);
                }
            },
            /**
             * Toggle.
             */
            toggle: function () {
                if(!$(event.currentTarget).hasClass('close-menu-sub')){
                    var html = $('html');
                    if (html.hasClass('nav-open')) {
                        html.removeClass('nav-open');
                        setTimeout(function () {
                            html.removeClass('nav-before-open');
                            $('.sections.nav-sections').find('li.ui-state-active-content').each(function(index, element) {
                                $(element).removeClass('ui-state-active');
                            });
                            $('.sections.nav-sections').find('ul.active-show').each(function(index, element) {
                                $(element).removeClass('active-show');
                            });
                            $('.sections.nav-sections').find('a.ui-menu-item-wrapper').each(function(index, element) {
                                $(element).removeClass('ui-state-active-content active-click-menu ui-state-active');
                            });
                        }, this.options.hideDelay);
                    } else {
                        html.addClass('nav-before-open');
                        setTimeout(function () {
                            html.addClass('nav-open');
                        }, this.options.showDelay);
                    }
                }
            },
        });
        return $.mage.menu;
    }
});