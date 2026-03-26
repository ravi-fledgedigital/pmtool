/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
define(
    [
        'Magento_Ui/js/form/components/button',
        'uiRegistry',
        'uiLayout',
        'mageUtils',
        'jquery',
        'underscore',
        'mage/translate',
        'Firebear_ImportExport/js/components/data/button'
    ],
    function (Element, registry, layout, utils, jQuery, _, $t, button) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    elementTmpl: 'Firebear_ImportExport/form/element/validate',
                    importCustomerGroupUrl: null,
                    error: '',
                    note: '',
                    visible: true,
                    imports: {
                        onHideError: '${$.parentName}.platforms:value'
                    }
                },

                initialize: function () {
                    this._super();
                    return this;
                },
                initObservable: function () {
                    return this._super()
                        .observe('error note');
                },
                action: function () {
                    this.importCustomerGroups();
                },
                loadForm: function () {
                    var ajaxSend = this.ajaxSend.bind(this);
                    this.getData().then(ajaxSend);
                },
                importCustomerGroups: function () {
                    this.error('');
                    var ajaxSend = this.ajaxSend.bind(this);
                    this.getParams().then(ajaxSend);
                },
                getParams: function () {
                    var form = jQuery.Deferred();
                    var formElements = new Array();
                    var self = this;
                    registry.get(
                        this.ns + '.' + this.ns + '.source',
                        function (object) {
                            var elems = object.elems();
                            _.each(
                                elems,
                                function (element) {
                                    if (element.visible() && element.componentType != 'container') {
                                        formElements.push(element.dataScope.replace('data.', '') + '+' + element.value())
                                    }
                                }
                            );
                            form.resolve(formElements);
                        }
                    );
                    return form.promise();
                },
                ajaxSend: function (elements) {
                    var form = jQuery.Deferred();
                    var self = this;
                    self.note('');
                    if (_.size(elements) > 0) {
                        var data = {
                            form_data: elements
                        };
                        jQuery.ajax(
                            {
                                type: "POST",
                                data: data,
                                showLoader: true,
                                url: self.importCustomerGroupUrl,
                                success: function (result, status) {
                                    if (result.error) {
                                        self.error($t(result.error));
                                    } else {
                                        self.note($t('The Customer Groups hav been imported.'))
                                    }
                                    form.resolve(true);
                                },
                                error: function () {
                                    self.error([$t('Error: The Customer Groups haven\'t been imported!')]);
                                },
                                dataType: "json"
                            }
                        );
                    }
                    return form.promise();
                },
                onHideError: function (value) {
                    this.error('');
                }
            }
        );
    }
);
