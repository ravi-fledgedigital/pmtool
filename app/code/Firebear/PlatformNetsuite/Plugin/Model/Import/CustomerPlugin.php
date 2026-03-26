<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\PlatformNetsuite\Plugin\Model\Import;

/**
 * Class CustomerPlugin
 * @package Firebear\PlatformNetsuite\Plugin\Model\Import
 */
class CustomerPlugin
{
    /**
     * @param \Firebear\ImportExport\Model\Import\Customer $model
     * @param $result
     * @return mixed
     */
    public function afterCustomChangeData(
        \Firebear\ImportExport\Model\Import\Customer $model,
        $result
    ) {
        $jobParamateres = $model->getParameters();
        if (!empty($jobParamateres['behavior_field_netsuite_customer_price_level_map'])) {
            foreach ($jobParamateres['behavior_field_netsuite_customer_price_level_map'] as $map) {
                if (!empty($result['price_level'])) {
                    if ($map['behavior_field_netsuite_customer_price_level_map_price_level_id']
                        == $result['price_level']) {
                        $result['group_id'] = $map['behavior_field_netsuite_customer_price_level_map_customer_group'];
                    }
                }
            }
        }

        if (!empty($result['firstname'])) {
            $firstname = explode(' ', $result['firstname']);
            $result['firstname'] = array_shift($firstname);
        }

        if (!empty($result['lastname'])) {
            $lastname = explode(' ', $result['lastname']);
            $result['lastname'] = end($lastname);
        }

        return $result;
    }
}
