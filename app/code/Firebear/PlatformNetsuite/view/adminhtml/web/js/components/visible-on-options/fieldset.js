/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/components/fieldset'
    ],
    function (Fieldset) {
        'use strict';
        return Fieldset.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    imports: {
                        toggleVisibility: '${$.parentName}.behavior_field_file_format:value'
                    },
                    openOnShow: true,
                    isShown: false,
                    inverseVisibility: false
                },

                /**
                 * Toggle visibility state.
                 *
                 * @param {String} selected
                 */
                toggleVisibility: function (selected) {
                    this.isShown = !Object.keys(this.valuesForOptions).length || (selected in this.valuesForOptions);
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);

                    if (this.openOnShow) {
                        this.opened(this.inverseVisibility ? !this.isShown : this.isShown);
                    }
                    this.setCustomVisibility();
                },

                initConfig: function () {
                    this._super();
                    return this;
                },

                initElement: function (elem) {
                    this._super();
                    this.setCustomVisibility();
                    return this;
                },

                setCustomVisibility: function () {
                    if (this.index == 'behavior_field_netsuite_credentials') {
                        var childrens = this.elems();
                        for (var i=0; i<childrens.length; i++) {
                            if (typeof childrens[i].hide === 'function') {
                                if (!this.isShown) {
                                    childrens[i].hide();
                                } else {
                                    childrens[i].show();
                                }
                            }
                        }
                    }
                }
            }
        );
    }
);
