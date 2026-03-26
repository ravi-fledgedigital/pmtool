define([
    'jquery',
    'Mirasvit_LayeredNavigation/js/config',
    'Mirasvit_LayeredNavigation/js/cache',
    'Mirasvit_LayeredNavigation/js/action/update-content',
    'Mirasvit_LayeredNavigation/js/action/apply-filter',
    'Mirasvit_LayeredNavigation/js/apply-button',
    'Mirasvit_LayeredNavigation/js/ajax/pagination',
    'Mirasvit_LayeredNavigation/js/helper/overlay',
    'Mirasvit_LayeredNavigation/js/sticky',
    'Mirasvit_LayeredNavigation/js/apply-button-interactions'
], function ($, config, cache, updateContent, applyFilter, applyButton, initPaging, overlay, sticky, initInteractions) {

    /**
     * Widget responsible for initializing AJAX layered navigation, toolbar and paging.
     */
    $.widget('mst.layeredNavigation', {
        options: {
            cleanUrl:                   '',
            overlayUrl:                 '',
            isSeoFilterEnabled:         false,
            isFilterClearBlockInOneRow: false,
            isHorizontalByDefault:      false
        },

        scrollTargetIdentifier: '',
        scrollListenerAdded: false,

        _create: function () {
            window.mNavigationConfigData = this.options;

            this._bind();

            initPaging();
        },

        _bind: function () {
            $(document).on(config.getAjaxCallEvent(), function (event, url, initiator, force) {
                applyButton.hide();

                let cachedData = cache.getData(url);
                if (cachedData) {
                    this.updatePageInstantlyMode(url, cachedData, initiator);
                } else {
                    this.requestUpdate(url, initiator, force);
                }
                this.addBrowserHistory(url);
                handleFiltersNavPositions();
            }.bind(this));

            if (typeof window.history.replaceState === 'function') {
                /** Browser back button */
                window.onpopstate = function (e) {
                    if (e.state && e.state.url !== undefined) {
                        window.location.href = e.state.url;
                    } else if (window.location.href.indexOf('#') < 0) {
                        window.location.reload();
                    }
                }.bind(this);
            }
        },

        _scrollToTop: function (initiator, isHorizontal) {
            let sidebar = $('.sidebar.sidebar-main');
            let target = sidebar.length ? sidebar.parent() : $();
            if (!target.length) {
                target = $('.toolbar.toolbar-products');
            }
            if (!target.length) {
                target = $('#m-navigation-product-list-wrapper');
            }

            // 1. initiator is present only in instant mode
            // 2. for confirmation mode scrolling to last selected filter makes no sense -> will be scrolled to top
            // 3. if filter selected in horizontal filters -> scroll to top
            if (!initiator || isHorizontal) {
                this.scrollTargetIdentifier = '';
                this._doScroll(target);
                return;
            }

            if (config.scrollToTopBehaviour() == 2 && initiator) {
                const filterBlock = initiator.closest('[data-attribute-code]');

                if (filterBlock) {
                    const identifier = filterBlock.attr('data-attribute-code');

                    this.scrollTargetIdentifier = filterBlock.attr('data-attribute-code');

                    // if filter is not in multiselect mode - scroll to top
                    if (!filterBlock.attr('data-is-multiselect')) {
                        this._doScroll(target);
                        return;
                    }

                    if (!this.scrollListenerAdded) {
                        // see collapsible-extended.js
                        $(document).on('mst-collapse-state-updated', (e, code) => {
                            this._scrollToCurrentFilter(code);
                        });

                        this.scrollListenerAdded = true;
                    }

                    return;
                }
            }

            this._doScroll(target)
        },

        _scrollToCurrentFilter: function (identifier) {
            if (identifier !== this.scrollTargetIdentifier) {
                return;
            }

            const target = $('[data-attribute-code="' + identifier + '"]:not(.mst-nav__horizontal-bar [data-attribute-code="' + identifier + '"])').closest('.filter-options-item');

            this._doScroll(target)
        },

        _doScroll: function (target) {
            let offset = 0;
            if (target.offset()) {
                offset = target.offset().top
            } else {
                const isOneLayout = !!document.getElementsByTagName('body')[0].classList.contains('page-layout-1column');
                if (isOneLayout) {
                    target = $('.toolbar.toolbar-products');
                    offset = target.offset() ? target.offset().top : 0;
                }
            }

            window.scrollTo({ top: offset, behavior: 'smooth' });
        },

        updatePageInstantlyMode: function (url, result, initiator) {
            let isHorizontal = initiator && initiator.closest('.mst-nav__horizontal-bar').length;

            updateContent.updateInstantlyMode(result, window.mNavigationConfigData.isHorizontalByDefault);

            if (config.mstStickySidebar()) {
                sticky();
            }

            if (config.scrollToTopBehaviour() != 0) {
                this._scrollToTop(initiator, isHorizontal);
            }
        },

        addBrowserHistory: function (url) {
            url = this.deleteForceModeQueryParam(url);
            window.history.pushState({url: url}, '', url);

            return true;
        },

        deleteForceModeQueryParam: function (url) {
            url = url.replace("?mstNavForceMode=instantly", "");
            url = url.replace("?mstNavForceMode=instantly&", "?");
            url = url.replace("&mstNavForceMode=instantly&", "&");
            url = url.replace("&mstNavForceMode=instantly", "");

            url = url.replace("?mstNavForceMode=by_button_click", "");
            url = url.replace("?mstNavForceMode=by_button_click&", "?");
            url = url.replace("&mstNavForceMode=by_button_click&", "&");
            url = url.replace("&mstNavForceMode=by_button_click", "");

            return url;
        },

        requestUpdate: function (url, initiator, force) {
            overlay.show();

            let data = {isAjax: true}
            if (force) {
                data.mstNavForceMode = 'instantly';
            }
            $.ajax({
                url:      url,
                data:     data,
                cache:    true,
                method:   'GET',
                success:  function (result) {
                    try {
                        result = $.parseJSON(result);
                        cache.setData(url, result);
                        let isHorizontal = initiator && initiator.closest('.mst-nav__horizontal-bar').length;

                        this.updatePageInstantlyMode(url, result, initiator);

                        if (config.scrollToTopBehaviour() != 0) {
                            this._scrollToTop(initiator, isHorizontal);
                        }
                    } catch (e) {
                        if (window.mNavigationAjaxscrollCompatibility !== 'true') {
                            console.log(e);

                            window.location = url;
                        }
                    }
                }.bind(this),
                error:    function () {
                    window.location = url;
                }.bind(this),
                complete: function () {
                    overlay.hide();
                    handleFiltersNavPositions();
                    config.load3rdPartyReviewWidgets();

                    if (config.isConfirmationMode()) {
                        initInteractions(applyButton);
                    }

                    // refresh sticky script after content updated
                    if (config.mstStickySidebar()) {
                        sticky();
                    }

                }.bind(this)
            });
        }

    });

    return $.mst.layeredNavigation;
});
