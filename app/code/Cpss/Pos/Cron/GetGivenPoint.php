<?php
//phpcs:ignoreFile
namespace Cpss\Pos\Cron;

use Cpss\Crm\Model\CpssApiRequest;
use Cpss\Crm\Model\ShopReceiptFactory;
use Cpss\Pos\Helper\Data;
use Cpss\Pos\Logger\Logger;
use Magento\Sales\Model\Order;

class GetGivenPoint
{
    protected $cpssApiRequest;
    protected $salesRealStoreOrders;
    protected $orderModel;
    protected $logger;
    protected $posHelper;

    public function __construct(
        CpssApiRequest $cpssApiRequest,
        ShopReceiptFactory $salesRealStoreOrders,
        Order $orderModel,
        Logger $logger,
        Data $posHelper
    ) {
        $this->cpssApiRequest = $cpssApiRequest;
        $this->salesRealStoreOrders = $salesRealStoreOrders;
        $this->orderModel = $orderModel;
        $this->logger = $logger;
        $this->posHelper = $posHelper;
    }

    public function getGivenPointAndSave($storeCode = '')
    {
        $this->logger->info("Start -> Get Given Point");
        $realStoreCollection = $this->salesRealStoreOrders->create()->getCollection();

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
            ->addFieldToFilter('payment_method', '-')
            ->addFieldToFilter('guest_purchase_flg', 0)
            ->addFieldToFilter('member_id', ['notnull' => true])
            // ->addFieldToFilter('return_purchase_id', ['notnull' => true])
            // ->addFieldToFilter('got_back_added_point', 0)
            ->addFieldToFilter('store_code', ['eq' => $storeCode])
            ->addFieldToFilter('created_at', ['lteq' => $to]);

        $this->saveAddPoint($realStoreCollection, "real_store", false, "Added_Point");
        $this->saveAddPoint($addedPointCollection, "ec", false, "Added_Point");
        $this->saveAddPoint($cancelPointAndGetBackCollection, "ec", false, "Got_Back_Added_Point");
        $this->updateUnchangedGotBackPointsForRealStore();
        $this->logger->info("End -> Get Given Point");
    }

    /**
     * @param $collectionData
     * @param string $orderType
     * @param bool $isUpdateGotBack
     * @param string $collection_name
     * @return void
     */
    public function saveAddPoint($collectionData, string $orderType = "real_store", bool $isUpdateGotBack = false, string $collection_name = "")
    {
        $count = $collectionData->count();

        $this->logger->info("Start -> Processing Collection", [
            "name"          => $collection_name,
            "type"          => $orderType,
            "total_records" => $count
        ]);

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/getGivenPoint.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Save Add Point Start============================');

        $storeCode = '';
        foreach ($collectionData as $order) {
            $storeCode = $order->getStoreCode();
            try {
                if ($orderType == "real_store") {
                    $aid = $order->getMemberId();
                    $hid = $order->getPurchaseId();
                } else {
                    $aid = $order->getCustomerId();
                    $hid = $order->getIncrementId();
                }

                if (!$aid || !$hid) {
                    $this->logger->error("AID or HID has no value", ["aid" => $aid, "hid" => $hid]);
                    continue;
                }

                $apiResponse = $this->cpssApiRequest->getGivenPoint($aid, $hid, $storeCode);

                $logger->info('API Response: ' . print_r($apiResponse, true));

                if ($apiResponse['http_code'] != 200) {
                    $this->logger->critical("Cpss -> Error Response", [
                        "aid"   => $aid,
                        "hid"   => $hid
                    ]);
                }

                $bodyContent = json_decode($apiResponse['Body'][0][0], true);
                $logger->info('API Response: ' . print_r($bodyContent, true));

                if (isset($bodyContent['result']['point']) && $bodyContent['result']['point'] > 0) {
                    if (isset($bodyContent['result']['direction']) && $bodyContent['result']['direction'] == "ADD") {
                        if (!$isUpdateGotBack) {
                            $order->setAddedPoint($bodyContent['result']['point']);
                            $addedDate = $this->formatDateFromCpss($bodyContent['result']['operate']);
                            $order->setAddedPointDate($addedDate);

                            $this->logger->info("Update Added Point", [
                                "id"    => $hid,
                                "point" => $bodyContent['result']['point'],
                                "date"  => $addedDate
                            ]);
                        }
                        if ($bodyContent['result']['getbackpoint']) {
                            if (($orderType == "real_store" && $order->getReturnPurchaseId() != "") || ($orderType == "ec")) {
                                $order->setGotBackAddedPoint($bodyContent['result']['getbackpoint']);
                                $getBackDate = $this->formatDateFromCpss($bodyContent['result']['getbackoperate']);
                                $order->setGotBackAddedPointDate($getBackDate);

                                $this->logger->info("Update Got Back Added Point", [
                                    "id"    => $hid,
                                    "point" => $bodyContent['result']['getbackpoint'],
                                    "date"  => $getBackDate
                                ]);
                            }
                        }
                    } else {
                        if (!$isUpdateGotBack) {
                            $order->setCancelPoint($bodyContent['result']['point']);
                            $cancelDate = $this->formatDateFromCpss($bodyContent['result']['operate']);
                            $order->setCancelPointDate($cancelDate);

                            $this->logger->info("Update Cancel Point", [
                                "id"    => $hid,
                                "point" => $bodyContent['result']['point'],
                                "date"  => $cancelDate
                            ]);
                        }
                        if ($bodyContent['result']['getbackpoint']) {
                            if (($orderType == "real_store" && $order->getReturnPurchaseId() != "") || ($orderType == "ec")) {
                                $order->setGotBackAddedPoint($bodyContent['result']['getbackpoint']);
                                $getBackDate = $this->formatDateFromCpss($bodyContent['result']['getbackoperate']);
                                $order->setGotBackAddedPointDate($getBackDate);

                                $this->logger->info("Update Got Back Added Point", [
                                    "id"    => $hid,
                                    "point" => $bodyContent['result']['getbackpoint'],
                                    "date"  => $getBackDate
                                ]);
                            }
                        }
                    }
                    $order->save();
                }
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage(), [
                    "name"          => $collection_name,
                    "type"          => $orderType,
                    "total_records" => $count,
                    "aid"           => $aid,
                    "hid"           => $hid
                ]);
            }
        }

        $logger->info('==========================Save Add Point End============================');

        $this->logger->info("End -> Processing Collection", [
            "name"          => $collection_name,
            "type"          => $orderType,
            "total_records" => $count
        ]);
    }

    public function updateUnchangedGotBackPointsForRealStore()
    {
        $realStoreCollection = $this->salesRealStoreOrders->create()->getCollection();

        $currentData = date("Y-m-d h:i:s");
        $previousDay = strtotime('-1 day', strtotime($currentData));
        $to = date('Y-m-d 23:59:59', $previousDay);

        $realStoreCollection
            ->addFieldToFilter('payment_method', '-')
            ->addFieldToFilter('guest_purchase_flg', 0)
            ->addFieldToFilter('transaction_type', 2)
            ->addFieldToFilter('added_point', ['neq' => 0])
            ->addFieldToFilter('added_point', ['neq' => new \Zend_Db_Expr("`got_back_added_point`")])
            ->addFieldToFilter('member_id', ['notnull' => true])
            ->addFieldToFilter('created_at', ['lteq' => $to]);

        $this->saveAddPoint($realStoreCollection, "real_store", true, "Got_Back_Added_Point");
    }

    public function formatDateFromCpss($date)
    {
        if (!$date) {
            return "";
        }

        $dateNow = $this->posHelper->convertTimezone(
            $date, //JST
            "UTC",
            "Y-m-d H:i:s"
        );

        // will save date as UTC (CPSS date is in UTC)
        return $dateNow;
    }
}
