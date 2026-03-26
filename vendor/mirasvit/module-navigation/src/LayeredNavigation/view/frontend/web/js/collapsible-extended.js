define([
    'jquery'
], function ($) {
    'use strict';

    // mage/collapsible
    return function (widget) {
        $.widget('mage.collapsible', widget, {
            storageFilterStateKey: '',

            _create: function() {
                this.storage = $.localStorage;

                const hasFilter = $('[data-mst-nav-filter]', this.element).length === 1;

                if (hasFilter && !this.element.closest('.mst-nav__horizontal-bar').length) {
                    this.initFiltersState();
                }

                if (hasFilter) {
                    $('.filter-options-title', this.element).on('click', this.updateFiltersState.bind(this));
                }

                return this._super();
            },

            _scrollToTopIfVisible: function () {
                return true;
            },

            _refresh: function () {
                //if ($('._opened', this.element).length) {
                //    this.options.active = true;
                //}

                let filtersState = this.storage.get('mst-filter-state');

                if (this.storageFilterStateKey) {
                    if (filtersState && filtersState[this.storageFilterStateKey]) {
                        this.options.active = true;
                    } else {
                        this.options.active = false;
                    }
                }

                const identifier = $('[data-attribute-code]', this.element).attr('data-attribute-code');

                this._super();

                if (identifier) {
                    $(document).trigger('mst-collapse-state-updated', [identifier]);
                }
            },

            initFiltersState: function () {
                let filtersState = this.storage.get('mst-filter-state');

                if (!filtersState) {
                    filtersState = {};

                    filtersState['customer_data_id'] = this.getCustomerDataId();

                    this.storage.set('mst-filter-state', filtersState);
                }

                // reset filters state on session change
                if (filtersState['customer_data_id'] != this.getCustomerDataId()) {
                    filtersState = {'customer_data_id': this.getCustomerDataId()};
                }

                this.storageFilterStateKey = this.getFilterKey();

                if (!this.storageFilterStateKey) {
                    return;
                }

                if (filtersState[this.storageFilterStateKey] === undefined) {
                    filtersState[this.storageFilterStateKey] = this.options.active;
                }

                this.storage.set('mst-filter-state', filtersState);
            },

            updateFiltersState: function() {
                let filtersState = this.storage.get('mst-filter-state');

                if (!filtersState || !this.storageFilterStateKey) {
                    $(document).trigger('mst-nav-filter-toggle', {
                        filterKey: null,
                        isOpen: null
                    });
                    return;
                }

                if (!filtersState['customer_data_id']) {
                    filtersState['customer_data_id'] = this.getCustomerDataId();
                }

                filtersState[this.storageFilterStateKey] = !filtersState[this.storageFilterStateKey];

                this.storage.set('mst-filter-state', filtersState);

                $(document).trigger('mst-nav-filter-toggle', {
                    
                    filterKey: this.storageFilterStateKey,
                    isOpen: filtersState[this.storageFilterStateKey]
                });
            },

            getFilterKey: function() {
                let key = $('[data-mst-nav-filter]', this.element).attr('data-mst-nav-filter');

                return key ? key.replace(/A\d{6,7}A/, '') : '';
            },

            getCustomerDataId: function() {
                let sessionStorage = this.storage.get('mage-cache-storage')
                    ? this.storage.get('mage-cache-storage')['customer']
                    : null;

                return sessionStorage ? sessionStorage['data_id'] : '';
            },
        });
        return $.mage.collapsible;
    }
});
