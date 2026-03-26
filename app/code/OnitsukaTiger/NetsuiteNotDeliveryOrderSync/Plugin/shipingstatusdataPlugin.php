<?php
namespace OnitsukaTiger\NetsuiteNotDeliveryOrderSync\Plugin;

use Magento\Sales\Model\OrderRepository;

class shipingstatusdataPlugin
{
    public function aftershipingstatusdata(\Clickend\Kerry\Model\shipingstatusdata $subject, $result, $ex_oreder_id)
    {
        if ($result['res']['res']['status']['ststus_code'] == '999') {
            $order = $this->order->loadByIncrementId($ex_oreder_id[1]);
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)->setStatus('notdelivery');
            $order->setData('netsuite_rma_status', '1');
            $order->save();
        }
        return $result;
    }
}
