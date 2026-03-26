<?php
declare(strict_types=1);

namespace OnitsukaTiger\ImportExport\Plugin;

use Firebear\ImportExport\Controller\Adminhtml\Export\Job\Save;

class UpdateBehaviorDataPlugin
{
    /**
     * @param Save $subject
     * @param $data
     * @return array
     */
    public function beforePrepareData(Save $subject, $data): array
    {
        $attribute_map_entity = array();
        $attribute_map_value  = array();
        if (isset($data['behavior_field_netsuite_attribute_map'])) {
            foreach ($data['behavior_field_netsuite_attribute_map'] as $attribute_map) {
                $attribute_map_entity [] = $attribute_map['behavior_field_netsuite_attribute_map_entity'] ?? '';
                $attribute_map_value  [] = $attribute_map['behavior_field_netsuite_attribute_map_system'] ?? '';
            }
            $attribute_map_system = array(
                'entity' => $attribute_map_entity,
                'value' => $attribute_map_value
            );
            $data['behavior_field_netsuite_attribute_map_system'] = $attribute_map_system;
        }

        return [$data];
    }
}