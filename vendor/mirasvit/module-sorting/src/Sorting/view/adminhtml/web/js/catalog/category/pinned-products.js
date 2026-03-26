define([
    'jquery'
], function ($) {
    'use strict';

    return function () {
        var pinnedProducts = $.extend({}, window.pinnedProductsData || {});

        function save() {
            $('#pinned_product_ids').val(JSON.stringify(pinnedProducts));
        }

        $(document).on('change', 'input[name="pin_to_top"]', function () {
            if (this.checked) {
                pinnedProducts[this.value] = '1';
            } else {
                delete pinnedProducts[this.value];
            }
            save();
        });

        $(document).on('click', 'input[name="pin_to_top"]', function (e) {
            e.stopPropagation();
        });

        save();
    };
});
