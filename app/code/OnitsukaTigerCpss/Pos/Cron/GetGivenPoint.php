<?php

namespace OnitsukaTigerCpss\Pos\Cron;

use Magento\Sales\Model\Order;
use OnitsukaTigerCpss\Pos\Helper\HelperData;

class GetGivenPoint extends \Cpss\Pos\Cron\GetGivenPoint
{
    public function getGivenPointAndSave($storeCode = '')
    {
        $this->logger->info("Start -> Get Given Point");
        $realStoreCollection = $this->salesRealStoreOrders->create()->getCollection();

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/getGivenPoint.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Get Given Point Log Start============================');

        $currentData = date("Y-m-d h:i:s");
        $previousDay = strtotime('-1 day', strtotime($currentData));
        $from = date('Y-m-d 00:00:00', strtotime("2022-10-20 00:00:00"));
        $to = date('Y-m-d 23:59:59', $previousDay);

        $addedPointCollection = $this->orderModel->getCollection()
            ->addFieldToFilter('customer_id', ['notnull' => true])
            ->addFieldToFilter('created_at', ['lteq' => $to])
            ->addFieldToFilter('created_at', ['gteq' => $from])
            ->addFieldToFilter('added_point', ['neq' => new \Zend_Db_Expr("`acquired_point`")])
            ->addFieldToFilter('status', [
                'in' => [
                    Order::STATE_COMPLETE,
                    Order::STATE_CLOSED,
                    HelperData::ORDER_STATUS_DELIVERED,
                    "partial_refund"
                ]
            ])
            ->addFieldToFilter('how_to_use', [
                ['neq' => "use_all"],
                ['null' => false]
            ]);
        $cancelPointAndGetBackCollection = $this->orderModel->getCollection()
            ->addFieldToFilter('customer_id', ['notnull' => true])
            ->addFieldToFilter('created_at', ['lteq' => $to])
            ->addFieldToFilter('created_at', ['gteq' => $from])
            ->addFieldToFilter('got_back_added_point', ['neq' => new \Zend_Db_Expr("`added_point`")])
            ->addFieldToFilter('status', [
                'in' => [
                    Order::STATE_CLOSED,
                    "partial_refund"
                ]
            ]);

        $realStoreCollection
            ->addFieldToFilter('added_point', 0)
            /*->addFieldToFilter('payment_method', '-')*/
            /*->addFieldToFilter('guest_purchase_flg', 0)*/
            ->addFieldToFilter('member_id', ['notnull' => true])
            // ->addFieldToFilter('return_purchase_id', ['notnull' => true])
            // ->addFieldToFilter('got_back_added_point', 0)
            ->addFieldToFilter('store_code', ['eq' => $storeCode])
            ->addFieldToFilter('created_at', ['lteq' => $to]);

        $logger->info('Created At: ' . $to);
        $logger->info('Query: ' . $realStoreCollection->getSelect());
        $logger->info('Point Data: ' . json_encode($realStoreCollection->getData()));

        $this->saveAddPoint($realStoreCollection, "real_store", false, "Added_Point");
        $this->saveAddPoint($addedPointCollection, "ec", false, "Added_Point");
        $this->saveAddPoint($cancelPointAndGetBackCollection, "ec", false, "Got_Back_Added_Point");
        $this->updateUnchangedGotBackPointsForRealStore();
        $this->logger->info("End -> Get Given Point");
    }
}
