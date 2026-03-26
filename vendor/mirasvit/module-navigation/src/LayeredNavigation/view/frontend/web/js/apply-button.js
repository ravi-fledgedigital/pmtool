define([
    'jquery',
    'Mirasvit_LayeredNavigation/js/action/apply-filter-instant-mode',
    'Mirasvit_LayeredNavigation/js/config',
    'Mirasvit_LayeredNavigation/js/cache',
    'Mirasvit_LayeredNavigation/js/action/update-content',
    'Mirasvit_LayeredNavigation/js/sticky',
    'Mirasvit_LayeredNavigation/js/apply-button-interactions'
], function ($, applyFilter, config, cache, updateContent, sticky, initInteractions) {
    'use strict';

    const applyButton = {
        selector:       '[data-element="mst-nav__applyButton"]',
        countSelector:  '[data-count]',
        buttonSelector: '[data-apply]',

        label1Selector: '[data-label-1]',
        labelNSelector: '[data-label-n]',

        $el: function () {
            return $(this.selector);
        },

        $count: function () {
            return $(this.countSelector, this.$el());
        },

        $button: function () {
            return $(this.buttonSelector, this.$el());
        },

        clear: function () {
            this.$count().html('');
            this.$button().attr('data-apply', '');
        },

        show: function () {
            this.$el().show();
        },

        hide: function () {
            this.$el().hide();
        },

        showLoader: function () {
            this.$el().addClass('_loading');
        },

        hideLoader: function () {
            this.$el().removeClass('_loading');
        },

        move: function ($initiator) {
            const applyButtonYOffset = $initiator.hasClass('mst-nav__slider') || $initiator.hasClass('swatch-option-link-layered');
            let x = 0;
            if (applyButtonYOffset) {
                const initiatorParent = $initiator.parent();
                x = initiatorParent.offset().left + initiatorParent.width();
            } else {
                x = $initiator.offset().left + $initiator.width();
            }

            let baseElement = this.getBaseElement($initiator);
            if (typeof baseElement.offset() == 'undefined') {
                baseElement = $(baseElement.context[0]);
            }

            let y = baseElement.offset().top - this.$el().height() / 2 + baseElement.height() / 2;
            // applyButton y offset for mobile
            if(window.innerWidth < 768 && applyButtonYOffset) {
                const labelElement = $initiator.parents('.filter-options-content')
                    ? $initiator.parents('.filter-options-content').siblings('.filter-options-title')
                    : null;
                const yPositionForSlidersAndSwatches = labelElement
                    ? labelElement.position().top + labelElement.outerHeight(true)
                    : (y - 40);

                y = yPositionForSlidersAndSwatches;
            }

            const buttonWidth = this.$el().outerWidth();
            const screenWidth = $(window).width();
            // check if button is out of screen
            if (x + buttonWidth > screenWidth) {
                x = screenWidth - buttonWidth;
            }

            this.$el().css("left", x)
                .css("top", y);
        },

        moveSticky: function ($initiator, oldInitiator) {

            const sidebar = document.querySelector('.sidebar.sidebar-main');
           
            let baseElement = this.getBaseElement($initiator);
            if (typeof baseElement.offset() == 'undefined') {
                baseElement = $(baseElement.context[0]);
            }

            const buttonTop = oldInitiator.top;
            const buttonLeft = oldInitiator.left;

            const initiatorTop = $initiator.offset().top
            const offset = initiatorTop - buttonTop;
            const yPosition = offset - this.$el().height() / 2 + baseElement.height() / 2;
            
            if (offset >= 0) {
                this.$el().css("left", buttonLeft)
                    .css("top", buttonTop);
                
                sidebar.scrollBy({
                    top: yPosition,
                    // behavior: "smooth",
                });
            } else {
                this.move($initiator)
            }
        },

        getBaseElement: function ($initiator) {
            let filterName = $initiator.closest('[data-mst-nav-filter]').data('mst-nav-filter');
            if (filterName === 'color') {
                return $initiator.children().first();
            }
            if (filterName === 'size') {
                return $initiator.children().first();
            }
            if (filterName === 'price') {
                return $('[data-element="slider"]', $initiator).first();
            }

            return $initiator
        },

        update: function (result) {
            let productsHtml = result['products'];
            let applyFilterUrl = result['url'];
            let productsCount = result['products_count'];

            this.$button().attr('data-apply', applyFilterUrl);
            this.$count().html(productsCount);
            this.toggleLabel(productsCount);

            if (productsHtml.length > 0) {//todo what for?
                $(config.getAjaxProductListWrapperId()).replaceWith(result['products']);
                $(config.getAjaxProductListWrapperId()).trigger('contentUpdated');
            }

            this.$button().on('click', function (e) {
                e.stopImmediatePropagation();

                const url = this.$button().attr('data-apply');
                applyFilter(url);
            }.bind(this))
        },

        load: function (url, cacheKey, force, oldInitiator = null) {
            this.clear();
            cacheKey = 'applyingMode:' + cacheKey;
            let cachedData = cache.getData(cacheKey);
            if (cachedData) {
                this.update(cachedData);
            } else {
                this._request(url, force, oldInitiator);
            }
        },

        _request: function (url, force, oldInitiator = null) {
            this.showLoader();

            let data = {isAjax: true};
            if (force) {
                data.mstNavForceMode = 'by_button_click';
            }

            let pendingFilterValues = [];
            if (config.preCalculationEnabled()) {
                pendingFilterValues = $('[data-element="filter"]._pending').map(function () {
                    return $(this).data('value');
                }).get();
            }

            $.ajax({
                url:      url,
                data:     data,
                cache:    true,
                method:   'GET',
                success:  function (response) {
                    let result = $.parseJSON(response);
                    let cacheKey = 'applyingMode:' + url;
                    cache.setData(cacheKey, result);

                    this.update(result);
                    // update filters if precalculation is enabled
                    if (config.preCalculationEnabled()) {
                        updateContent.filtersUpdate(result.leftnav);

                        if (config.mstStickySidebar()) {
                            sticky();
                        }

                        pendingFilterValues.forEach(function (value) {
                            $('[data-element="filter"][data-value="' + value + '"]').addClass('_pending');
                        });

                        initInteractions(this);
                    }

                    if (oldInitiator) {
                        setTimeout(() => {
                            let $initiator = config.mstStickySidebar() ? oldInitiator.initiator : oldInitiator;
                            $initiator = this.restoreInitiator($initiator);
                            if ($initiator) {
                                if (config.mstStickySidebar()) {
                                    this.moveSticky($initiator, oldInitiator);
                                } else {
                                    this.move($initiator);
                                }
                                // this.show();
                            }
                        }, 100);
                    }
                }.bind(this),
                error:    function () {
                    window.location = url;
                }.bind(this),
                complete: function () {
                    this.hideLoader();
                }.bind(this)
            });
        },

        toggleLabel: function (number) {

            number = parseInt(number, 10);

            if (number === 1) {
                $(this.labelNSelector, this.$el()).hide();
                $(this.label1Selector, this.$el()).show();
            } else {
                $(this.label1Selector, this.$el()).hide();
                $(this.labelNSelector, this.$el()).show();
            }
        },

        restoreInitiator: function (oldInitiator) {
            let initiator;
            let selector = oldInitiator.find('input').attr('id');
            const sidebarSelector = '.sidebar.sidebar-main';

            if (selector) {
                selector = '#' + selector.replace(/([^\w \\])/g,'\\$1');
                const input = document.querySelector(sidebarSelector + ' ' + selector);
                initiator = input.parentNode.closest('li');
                initiator = $(initiator);
            } else if (oldInitiator.find(sidebarSelector + ' .filter-options .mst-nav__rating .rating-result').length > 0) {
                initiator = $(sidebarSelector + ' .filter-options .mst-nav__rating').find(`[data-value='${oldInitiator.data().value}']`);
            } else if (oldInitiator.data().value) {
                initiator = $(sidebarSelector + ' .filter-options').find(`[data-value='${oldInitiator.data().value}']`);
            } else if (oldInitiator.data().mstNavFilter) {
                initiator = $(sidebarSelector + ' .filter-options').find(`[data-mst-nav-filter='${oldInitiator.data().mstNavFilter}']`);
            } 
            return initiator;    
        },

    };

    initInteractions(applyButton);

    return applyButton;
});

