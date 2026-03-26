define([
    'jquery',
    'Magento_Theme/js/model/breadcrumb-list'
], function ($, breadcrumbList) {
    'use strict';

    return function (widget) {

        $.widget('mage.breadcrumbs', widget, {
            options: {
                categoryUrlSuffix: '',
                useCategoryPathInUrl: false,
                product: '',
                categoryItemSelector: '.category-item',
                menuContainer: '[data-action="navigation"] > ul'
            },

            /** @inheritdoc */
            _render: function () {
                const referrerUrl  = document.referrer;
                const brandsConfig = this.options.brandConfig;

                let brand = null;

                if (brandsConfig) {
                    for (let i = 0; i < brandsConfig.length; i++) {
                        if (i == 0) {
                            continue;
                        }
    
                        if (referrerUrl.indexOf(brandsConfig[i].url) == 0) {
                            if (!brand) {
                                brand = brandsConfig[i];
                            } else if (brandsConfig[i].url.length > brand.url.length) {
                                brand = brandsConfig[i];
                            }
                        }
                    }
                }

                if (brand) {
                    this.addBrandCrumbs(brand);
                }

                this._super();
            },

            addBrandCrumbs: function (config) {
                breadcrumbList.push({
                    'name': 'brands',
                    'label': this.options.brandConfig[0].label,
                    'link': this.options.brandConfig[0].url,
                    'title': ''
                });

                breadcrumbList.push({
                    'name': config.label.toLowerCase(),
                    'label': config.label,
                    'link': config.url,
                    'title': ''
                });
            }
        });

        return $.mage.breadcrumbs;
    };
});
