/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */


define([
    'uiRegistry'
], function (reg) {
    'use strict';

    return function (form) {

        return form.extend({
            recorrectMapData : function (data) {

                data = this._super(data);
                var self = this;
                var objects = reg.get(self.ns + "." + self.ns + ".behavior_field_netsuite_attribute_map_container.behavior_field_netsuite_attribute_map");
                if (typeof objects != 'undefined') {
                    var list = [];
                    _.each(objects.elems(), function (elem, index) {
                        var listSecond = {};
                        _.each(elem.elems(), function (elemTop) {
                            listSecond[elemTop.prefixName] = elemTop.value();
                        });
                        list[index] = listSecond;
                    });

                    if (_.size(list) > 0) {
                        if (_.size(data.behavior_field_netsuite_attribute_map_export)) {
                            data.behavior_field_netsuite_attribute_map_export.delete = [];
                            data.behavior_field_netsuite_attribute_map_export.order = [];
                            data.behavior_field_netsuite_attribute_map_export.value = [];
                            data.behavior_field_netsuite_attribute_map_system.entity = [];
                            data.behavior_field_netsuite_attribute_map_system.value = [];
                            _.each(list, function (elem, index) {
                                data.behavior_field_netsuite_attribute_map_export.delete[index] = "";
                                data.behavior_field_netsuite_attribute_map_export.order[index] = "";
                                data.behavior_field_netsuite_attribute_map_export.value[index] = elem["behavior_field_netsuite_attribute_map_export.value"];
                                data.behavior_field_netsuite_attribute_map_system.entity[index] = elem["behavior_field_netsuite_attribute_map_system.entity"];
                                data.behavior_field_netsuite_attribute_map_system.value[index] = elem["behavior_field_netsuite_attribute_map_system.value"];
                            });
                        }
                    };

                }
                return data;
            }
        });
    }
});
