define([
    'jquery',
    'Mirasvit_LayeredNavigation/js/config',
    'Mirasvit_LayeredNavigation/js/helper/url',
    'Mirasvit_LayeredNavigation/js/action/apply-filter',
    'Magento_Catalog/js/product/list/toolbar'
], function ($, config, url, applyFilter) {
    'use strict';

    /**
     * We rewrite this widget (requirejs-config.js) to enable AJAX for toolbar functionality.
     */
    $.widget('mst.productListToolbarForm', $.mage.productListToolbarForm, {

        /**
         * @param {String} paramName
         * @param {*} paramValue
         * @param {*} defaultValue
         */
        changeUrl: function (paramName, paramValue, defaultValue) {
            return config.isAjax()
                ? this._changeAjaxUrl(paramName, paramValue, defaultValue)
                : this._changeStdUrl(paramName, paramValue, defaultValue);

        },

        /**
         * Change URL when AJAX enabled.
         *
         * @param {String} paramName
         * @param {*} paramValue
         * @param {*} defaultValue
         */
        _changeAjaxUrl: function (paramName, paramValue, defaultValue) {
            var link;

            // ignore duplicate requests
            if (this._isToolbarLock()) {
                return false;
            }

            window.mNavigationConfigData.cleanUrl = this._getToolbarCleanUrl(
                paramName,
                paramValue,
                defaultValue,
                this.options
            );

            //seo filter
            link = url.getLink(window.mNavigationConfigData.cleanUrl);

            applyFilter.apply(link);
        },

        /**
         * Change URL without AJAX.
         *
         * @param {String} paramName
         * @param {*} paramValue
         * @param {*} defaultValue
         */
        _changeStdUrl: function (paramName, paramValue, defaultValue) {
            var decode    = window.decodeURIComponent,
                urlPaths  = this.options.url.split('?'),
                baseUrl   = urlPaths[0],
                paramData = this.getUrlParams(),
                currentPage = this.getCurrentPage(),
                newPage, form, params, key, input, formKey;

            if (currentPage > 1 && paramName === this.options.limit) {
                newPage = Math.floor(this.getCurrentLimit() * (currentPage - 1) / paramValue) + 1;

                if (newPage > 1) {
                    paramData[this.options.page] = newPage;
                } else {
                    delete paramData[this.options.page];
                }
            }

            paramData[paramName] = paramValue;

            if (this.options.post) {
                form = document.createElement('form');
                params = [this.options.mode, this.options.direction, this.options.order, this.options.limit];

                for (key in paramData) {
                    if (params.indexOf(key) !== -1) { //eslint-disable-line max-depth
                        input = document.createElement('input');
                        input.name = key;
                        input.value = paramData[key];
                        form.appendChild(input);
                        delete paramData[key];
                    }
                }
                formKey = document.createElement('input');
                formKey.name = 'form_key';
                formKey.value = this.options.formKey;
                form.appendChild(formKey);

                paramData = $.param(paramData);
                baseUrl += paramData.length ? '?' + paramData : '';

                form.action = baseUrl;
                form.method = 'POST';
                document.body.appendChild(form);
                form.submit();
            } else {
                if (paramValue == defaultValue) { //eslint-disable-line eqeqeq
                    delete paramData[paramName];
                }
    
                paramData = $.param(paramData);
    
                //fix incorrect symbols in url
                paramData = paramData.replace(/%2C/g, ",");
                
                location.href = baseUrl + (paramData.length ? '?' + paramData : '');
            }
        },

        _getToolbarCleanUrl: function (paramName, paramValue, defaultValue, options) {
            var decode    = window.decodeURIComponent,
                urlPaths  = options.url.split('?'),
                baseUrl   = urlPaths[0],
                paramData = this.getUrlParams(),
                currentPage = this.getCurrentPage(),
                parameters, i, newPage;

            if (currentPage > 1 && paramName === options.limit) {
                newPage = Math.floor(this.getCurrentLimit() * (currentPage - 1) / paramValue) + 1;

                if (newPage > 1) {
                    paramData[options.page] = newPage;
                } else {
                    delete paramData[options.page];
                }
            }

            paramData[paramName] = paramValue;

            if (paramValue == defaultValue && !this.options.post) { //eslint-disable-line eqeqeq
                delete paramData[paramName];
            }

            paramData = $.param(paramData);

            //fix incorrect symbols in url
            paramData = paramData.replace(/%2C/g, ",");

            return baseUrl + (paramData.length ? '?' + paramData : '');
        },

        /**
         * Page contains 2 toolbars: before and after product listing, hence, multiple instances of this widget exists.
         * We use this hack in order to stop multiple requests from being called.
         *
         * @return {Boolean}
         */
        _isToolbarLock: function () {
            if (window.blockToolbar) {
                return true;
            }

            window.blockToolbar = true;
            setTimeout(function () {
                window.blockToolbar = false;
            }, 300);

            return false;
        }
    });

    return $.mst.productListToolbarForm;
});
