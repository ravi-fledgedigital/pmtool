define([
    'jquery',
    'jquery/jstree/jquery.jstree',
], function ($) {
    'use strict';

    $.widget('mage.categoryTree', {
        options: {
            url: '',
            selectedCategories: []
        },

        _create: function () {
            var self = this;
            
            $(this.element).jstree({
                plugins: ['checkbox'],
                core: {
                    data: {
                        url: this.options.url,
                        data: function (node, ) {
                            return { 
                                id: node.id === '#' ? 1 : node.id, 
                                selectedCategories: $(self.element).data('selected-categories') ?? []
                            };
                        }
                    },
                    themes: {
                        name: 'default',
                        responsive: true
                    }
                },
                checkbox: {
                    three_state: false
                }
            }).on('loaded.jstree', function () {
                if (self.options.selectedCategories.length) {
                    $(this).jstree('select_node', self.options.selectedCategories);
                }
            }).on('changed.jstree', function (e, data) {
                var selectedNodes = $(this).jstree('get_selected');
                $('input.category-tree-field').val(selectedNodes.join(','));
            });
        }
    });

    return function (config, element) {
        $(element).categoryTree(config);
    };
});
