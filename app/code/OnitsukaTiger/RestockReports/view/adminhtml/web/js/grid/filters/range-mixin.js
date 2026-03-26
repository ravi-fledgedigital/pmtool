/* eslint-disable */
define([
    'underscore',
    'uiLayout',
    'mageUtils',
    'Magento_Ui/js/form/components/group',
    'mage/translate'
], function (_, layout, utils, Group, $t) {
    'use strict';

    return function (Group) {
        return Group.extend({
            /**
             * Clears children data.
             *
             * @returns {Range} Chainable.
             */
            clear: function () { // Correction for space-before-function-paren
                this.elems.each('clear');
                this.elems.each(function (elm) { // Correction for space-before-function-paren
                    [].forEach.call(
                        document.getElementsByClassName('_has-datepicker'),
                        function (el) { // Correction for space-before-function-paren
                            if (elm.inputName === el.name) { // Correction for eqeqeq
                                el.value = '';
                            }
                        }
                    );
                });

                return this;
            }
        });
    };
}); // Correction for eol-last
/* eslint-enable */
