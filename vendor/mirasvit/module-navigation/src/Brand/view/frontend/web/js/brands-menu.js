define([
    'jquery',
    'uiComponent',
    'ko',
    'domReady!',
    'mage/url',
    'Magento_Ui/js/modal/modal'
], function ($, Component, ko, domready, url, modal) {
    'use strict';

    return Component.extend({
        activeLetter:   '',
        brandsByLetter: [],

        defaults: {
            template:         'Mirasvit_Brand/menu/content',
            contentSelector:  '.mst-brand__menu-modal',
            menuItemSelector: '.mst__menu-item-brands',
            noBrandsMessage:  $.mage.__('There are no defined brands'),
            keyFeatured:      'featured'
        },

        initialize: function () {
            this.brands = ko.observableArray([]);
            this._super();

            let popup = $(this.contentSelector);

            // Initialize the modal
            popup.modal({
                type:       'popup',
                modalClass: 'mst-brand__modal',
                responsive: true,
                buttons:    []
            });

            this.getBrandsMenu();

            $(this.menuItemSelector).on('click', function (e) {
                if (window.innerWidth > 768) {
                    e.preventDefault();
                    popup.modal('openModal');
                }
            });
        },

        getBrandsMenu: function (letter = null) {
            if (this.activeLetter === letter) {
                letter = null;
            }

            const contentIndex = letter ? letter : this.keyFeatured;

            let self = this;

            if (self.brandsByLetter[contentIndex]) {
                self.brands(self.brandsByLetter[contentIndex]);
                self.activateLetterMenu(letter);
            } else {
                $.ajax({
                    url:      window.ajaxMenuUrl,
                    dataType: 'json',
                    type:     'GET',
                    data:     letter ? {letter: letter} : {},
                    success:  function (data) {
                        self.brands(data.brands);
                        self.activateLetterMenu(letter);
                        self.brandsByLetter[contentIndex] = data.brands;
                    },
                    error:    function (error) {
                        console.error('Error fetching brands menu: ', error);
                    }
                });
            }
        },

        activateLetterMenu: function (letter = null) {
            this.activeLetter = letter ? letter : this.keyFeatured;
            $(this.contentSelector + ' [data-letter]').removeClass('_active');
            $(this.contentSelector + ` [data-letter|=${letter}]`).addClass('_active');
        }
    });
});
