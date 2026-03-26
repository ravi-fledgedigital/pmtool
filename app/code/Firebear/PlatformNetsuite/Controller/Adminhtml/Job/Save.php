<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job\Save as JobController;

/**
 * Class Save
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Save extends JobController
{
    /**
     * @param $data
     */
    protected function spliteBeahivorData(&$data)
    {
        if (!empty($data['behavior_field_netsuite_customer_price_level_map'])) {
            $data['behavior_data']['behavior_field_netsuite_customer_price_level_map'] =
                $data['behavior_field_netsuite_customer_price_level_map'];
        }
        if (isset($data['behavior_field_netsuite_order_is_in_m2'])) {
            $data['behavior_data']['behavior_field_netsuite_order_is_in_m2'] =
                $data['behavior_field_netsuite_order_is_in_m2'];
        }
        if (isset($data['behavior_field_netsuite_invoice_is_in_m2'])) {
            $data['behavior_data']['behavior_field_netsuite_invoice_is_in_m2'] =
                $data['behavior_field_netsuite_invoice_is_in_m2'];
        }
        if (isset($data['behavior_field_netsuite_shipment_is_in_m2'])) {
            $data['behavior_data']['behavior_field_netsuite_shipment_is_in_m2'] =
                $data['behavior_field_netsuite_shipment_is_in_m2'];
        }

        parent::spliteBeahivorData($data);
    }
}
