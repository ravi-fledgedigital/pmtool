define([
    'jquery',
    'Mirasvit_LayeredNavigation/js/config'
], function ($, configProvider) {
        'use strict';

        return function(config, element) {
            var param     = $('#mst-nav__search-filter').attr('name');
            var url       = location.href;
            var origQuery = location.search;

            var suggestionContainer = $('#mst-nav__search-suggestion');

            url = url.replace(origQuery, '');

            $('#mst-nav__search-filter').on('keyup change', function (e) {
                var value = this.value;

                if (configProvider.isSearchFilterOptions()) {
                    suggest(suggestionContainer, value);
                }

                if (configProvider.isSearchFilterFulltext()) {
                    prepareSearch(e, url, origQuery, param, value);
                }
            });
        };

        function prepareSearch(e, url, origQuery, param, value) {
            if (e.keyCode === 13) {
                $('#mst-nav__search-apply').click();
            }

            var query = '';

            if (!origQuery) {
                query = '?' + param + '=' + value;
            } else if (origQuery.indexOf(param) >= 0) {
                origQuery = origQuery.replace('?', '');

                var queryArray = origQuery.split('&');

                queryArray.forEach(function (item, key) {
                    if (item.indexOf(param) >= 0) {
                        var itemArray = item.split('=');
                        itemArray[1] += ',' + value;
                        item = itemArray.join('=');
                        queryArray[key] = item;
                    }
                });

                query = '?' + queryArray.join('&');
            } else {
                query = origQuery + '&' + param + '=' + value;
            }

            $('#mst-nav__search-apply').attr('href', url + query);
        }

        function getFilterIdentifier(item) {
            var options = $('.mst-nav__label', item);

            var identifier = options.attr('data-mst-nav-filter')
                ? options.attr('data-mst-nav-filter')
                : $('.swatch-attribute-options', item).attr('data-mst-nav-filter');

            if (!identifier) {
                return '';
            }

            identifier = identifier.replace(/A\d{6}A/, '');

            return identifier;
        }

        function suggest(suggestionContainer, value) {
            suggestionContainer.empty();

            if (!value) {
                suggestionContainer.hide();
                return;
            }

            value = value.toLowerCase();

            var filters           = $('.sidebar-main').find('.filter-options-item');
            var horizontalFilters = $('.mst-nav__horizontal-bar').find('.filter-options-item');

            filters = filters.add(horizontalFilters);

            var suggested = filters.clone();

            var uniqueFilterCodes = [];

            suggested.each(function (index, item) {
                var identifier = getFilterIdentifier(item);

                if (!uniqueFilterCodes.includes(identifier) && identifier != 'search') {
                    uniqueFilterCodes.push(identifier);
                }
            }.bind(this));

            suggested.each(function (index, item) {
                // do not add search filter in suggested
                if (index === 0) {
                    return;
                }

                var identifier = getFilterIdentifier(item);

                if (!uniqueFilterCodes.includes(identifier)) {
                    return;
                }

                uniqueFilterCodes.splice(uniqueFilterCodes.indexOf(identifier), 1);

                var filter = $(item);

                filter.children('.filter-options-title').each(function (i, title) {
                    $(title).removeClass('filter-options-title').addClass('mst-nav__suggestion-title');
                });

                var searchbox = filter.find('[data-element="search"]');

                if (searchbox) {
                    searchbox.remove();
                }

                var cloned        = filter.clone();
                var filterContent = filter.children('.filter-options-content');

                // do not add slider filter into suggested filters
                if (filterContent.children('.mst-nav__slider').length) {
                    return;
                }

                var removed      = 0;
                var optionsCount = 0;

                if (filterContent.children('.swatch-attribute').length) { // filter swatch options
                    var swatchOptions = filterContent.children('.swatch-attribute')
                        .children('.swatch-attribute-options')
                        .children('a');

                    optionsCount  = swatchOptions.length;

                    swatchOptions.each(function (i, swatch) {
                        var swatchLabel = $(swatch).children('.swatch-option').attr('data-option-label');

                        if (!swatchLabel || swatchLabel.toLowerCase().indexOf(value) === -1) {
                            $(swatch).remove();
                            removed++;
                        }
                    });
                } else if (filterContent.children('.mst-nav__label').length) { // filter options
                    var linkOptions = $('.item', filterContent);

                    linkOptions.attr('data-hidden', false);
                    linkOptions.attr('data-search-hidden', false);
                    linkOptions.attr('data-letter-hidden', false);

                    optionsCount = linkOptions.length;

                    linkOptions.each(function (i, link) {
                        var linkLabel = $(link).children('a')
                            .children('label')
                            .text();

                        linkLabel = linkLabel.replace(/\d*item(s)?/, '');
                        linkLabel = linkLabel.trim();

                        if (!linkLabel || linkLabel.toLowerCase().indexOf(value) === -1 || $('input:disabled', $(link)).length) {
                            $(link).remove();
                            removed++;
                        } else { // highlight option match
                            var start = linkLabel.toLowerCase().indexOf(value);
                            var match = linkLabel.substr(start, value.length);
                            var regex = new RegExp(match, 'g');

                            linkLabel = linkLabel.replace(regex, '<span class="mst-nav__suggest-match">' + match + '</span>');

                            $(link).children('a').children('label').html(linkLabel);
                        }
                    });
                }

                if (optionsCount === removed) {
                    var attributeLabel = cloned.children('.mst-nav__suggestion-title').text();
                    attributeLabel = attributeLabel.trim();

                    if(attributeLabel.toLowerCase().indexOf(value) >= 0) {
                        var start = attributeLabel.toLowerCase().indexOf(value);
                        var match = attributeLabel.substr(start, value.length);
                        var regex = new RegExp(match, 'g');

                        attributeLabel = attributeLabel.replace(regex, '<span class="mst-nav__suggest-match">' + match + '</span>');

                        cloned.children('.mst-nav__suggestion-title').html(attributeLabel);

                        appendInitScript(cloned);
                        suggestionContainer.append(cloned);
                    }
                } else {
                    appendInitScript(filter);
                    suggestionContainer.append(filter);
                }
            }.bind(this));

            if (suggestionContainer.children().length) {
                suggestionContainer.show();
            } else {
                suggestionContainer.hide();
            }

            $(document).on('click', function(e) {
                suggestionContainer.hide();
            });

            suggestionContainer.trigger('contentUpdated');
        }

        function appendInitScript(filter) {
            const filterKey    = 'data-mst-nav-filter';
            const rendererPath = 'Mirasvit_LayeredNavigation/js/renderer/label-renderer';

            var filterContent = filter.children('.filter-options-content');
            var initScript    = document.createElement('script');
            var attributeCode = filter.find('[' + filterKey + ']').attr(filterKey);
            var filterCode    = '[' + filterKey + '=' + attributeCode + ']';
            var initBodyArray = {
                [filterCode]: {
                    [rendererPath]: []
                }
            };

            initScript.type = 'text/x-magento-init';

            $(initScript).text(JSON.stringify(initBodyArray));

            filterContent.append($(initScript));
        }
    }
);
