define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mst.navHelperAlphabetical', {
        letters:         [],
        visibleSelector: '[data-letter][data-value][data-search-hidden = false]',

        options: {
            shouldDisplay: false,
            limit:         5
        },

        _create: function () {
            if (!this.options.shouldDisplay) {
                return;
            }

            const options = $('[data-element="filter"][data-value]', this.element);

            if (!options.length) {
                return;
            }

            this.createAlphabeticalIndex(options);
        },

        createAlphabeticalIndex: function (options) {
            this.letters = [];

            options.each(function (idx, option) {
                option = $(option);

                let label = $('label', option).length ? $('label', option).text().trim() : $('[data-option-label]', option).attr('data-option-label');

                let letter = label.substring(0, 1).toUpperCase();

                letter = letter.match(/^[\p{Script=Latin}\p{Script=Cyrillic}\u0590-\u05FF]$/u) ? letter : '#';

                if (this.letters.indexOf(letter) < 0) {
                    this.letters.push(letter);
                }
            }.bind(this));

            this.letters.sort();

            const $container = $('<div />');
            $container.addClass('mst-nav__alphabetical');

            this.letters.forEach(function (letter) {
                const $letter = $('<span />');
                $letter.text(letter);
                $letter.on('click', this.filterOptions.bind(this));
                $letter.on('click', this.handleVisibility.bind(this));

                $container.append($letter);
            }.bind(this));

            $('[data-holder=alphabetical]', this.element).append($container);

            this.element.on('alphabetical', this.handleVisibility.bind(this));
        },

        filterOptions: function (e) {
            const letter = $(e.target);

            if (letter.hasClass('_checked')) {
                letter.removeClass('_checked');
            } else {
                letter.addClass('_checked');
            }

            const checkedLetters = this.getCheckedLetters();

            $(this.element).trigger('showhide'); // communicate with sizeLimiter

            $('[data-letter]', this.element).each(function () {
                const option = $(this);

                option.attr('data-hidden', 'false');

                if (checkedLetters.length && checkedLetters.indexOf(option.attr('data-letter')) < 0) {
                    option.attr('data-letter-hidden', 'true');
                } else {
                    option.attr('data-letter-hidden', 'false');
                }
            });
        },

        handleVisibility: function () {
            const visibleFilters = $(this.visibleSelector, this.element);

            if (visibleFilters.length < this.options.limit) {
                $('.mst-nav__alphabetical span', this.element).hide();

                return;
            }

            let visibleLetters = [];

            visibleFilters.each(function () {
                const filter = $(this);

                if (visibleLetters.indexOf(filter.attr('data-letter')) < 0) {
                    visibleLetters.push(filter.attr('data-letter'));
                }
            });

            if (visibleLetters.length) {
                $('.mst-nav__alphabetical span', this.element).hide();

                let selectors = [];

                visibleLetters.forEach(function (letter) {
                    selectors.push('.mst-nav__alphabetical span:contains(' + letter + ')');
                });

                $(selectors.join(', '), this.element).show();
            }
        },

        getCheckedLetters: function () {
            return $('.mst-nav__alphabetical span._checked', this.element).text().split('');
        }
    });

    return $.mst.navHelperAlphabetical;
});
