<?php

namespace Cpss\Pos\Helper;

use Cpss\Crm\Helper\AbstractHelper as CpssHelper;
use Cpss\Pos\Cron\UpdatePosData;
use Cpss\Pos\Logger\CsvLogger;
use Cpss\Pos\Logger\Logger as PosLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList as DirectorySystem;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditMemoCollection;
use Magento\Store\Model\ScopeInterface;

class CreateCsv extends AbstractHelper
{
    const EC_POS_ID = '0001'; //担当者ID
    const CPSS_CSV_DIR = "cpss/point_integration/";
    const CPSS_CSV_RECOVERY_DIR = "cpss_recovery/point_integration/";
    const CSV_TYPE_HEADER = "HEADER";
    const CSV_TYPE_DETAIL = "DETAIL";
    const CSV_TYPE_PRODUCT = "PRODUCT";
    const CSV_TYPES = [self::CSV_TYPE_HEADER, self::CSV_TYPE_DETAIL, self::CSV_TYPE_PRODUCT];

    protected $directory;
    protected $fileFactory;
    protected $directorySystem;
    protected $filesystem;
    protected $shopReceiptFactory;
    protected $shopItemsModel;
    protected $shopReceiptCollection;
    protected $realStoreItemsCollection;
    protected $timezone;
    protected $logger;
    protected $scopeConfig;
    protected $orderCollectionFactory;
    protected $orderResource;
    protected $creditMemoCollection;
    protected $csvLogger;
    protected $posHelper;
    protected $isRecover;
    protected $purchaseIds;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    const HEADERS = [
        'HEADER' => [
            'sales_id',
            'member_id',
            'sales_time',
            'store_id',
            'staff_id',
            'slip_kubun',
            'sales_total_amount',
            'discount_total_amount',
            'tax_total_amount',
            'source_sales_id'
        ],
        'DETAIL' => [
            'sales_id',
            'product_id',
            'quantity_num',
            'sales_amount',
            'discount_amount',
            'tax_amount'
        ],
        'PRODUCT' => [
            /*'商品ID',
            '商品名',
            '大分類',
            '中分類',
            '小分類',
            '定価',
            'ポイント付与対象'*/
            'product_id',
            'product_name',
            'major_category_code',
            'middle_category_code',
            'minor_category_code',
            'point_flag'
        ]
    ];

    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\HelperData
     */
    protected $cpssHelperData;

    public function __construct(
        Context $context,
        DirectorySystem $directorySystem,
        Filesystem $_filesystem,
        FileFactory $fileFactory,
        \Cpss\Crm\Model\ShopReceipt $shopReceiptFactory,
        \Cpss\Pos\Model\PosData $shopItemsModel,
        \Cpss\Crm\Model\ResourceModel\ShopReceipt\CollectionFactory $shopReceiptCollection,
        \Cpss\Pos\Model\ResourceModel\PosData\CollectionFactory $realStoreItemsCollection,
        TimezoneInterface $timezone,
        PosLogger $logger,
        ScopeConfigInterface $scopeConfig,
        OrderCollectionFactory $orderCollectionFactory,
        OrderResource $orderResource,
        CreditMemoCollection $creditMemoCollection,
        CsvLogger $csvLogger,
        \Cpss\Pos\Helper\Data $posHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $cpssHelperData,
        private \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest
    ) {
        $this->directorySystem = $directorySystem;
        $this->directory = $_filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->filesystem = $_filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $this->fileFactory = $fileFactory;
        $this->shopReceiptFactory = $shopReceiptFactory;
        $this->shopItemsModel = $shopItemsModel;
        $this->shopReceiptCollection = $shopReceiptCollection;
        $this->realStoreItemsCollection = $realStoreItemsCollection;
        $this->timezone = $timezone;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderResource = $orderResource;
        $this->creditMemoCollection = $creditMemoCollection;
        $this->csvLogger = $csvLogger;
        $this->posHelper = $posHelper;
        $this->storeManager = $storeManager;
        $this->cpssHelperData = $cpssHelperData;
        parent::__construct($context);
    }

    public function createCsv($filename, $rowData, $type, $storeCode = 'sg')
    {
        try {
            $this->createFileDirectory($this->getPath());
            $directory = $this->getPath();
            $file = $directory . $storeCode . '/' . $filename;

            if ($type == self::CSV_TYPE_PRODUCT) {
                //Create empty file only
                $stream = $this->directory->openFile($file);
                $stream->close();
                return;
            }

            $this->csvLogger->info("Create $filename");

            $filePath = $this->directorySystem->getPath(DirectoryList::VAR_DIR) . "/" . $file;

            $lockResult = false;
            $fileExists = false;
            try {
                if (file_exists($filePath)) {
                    $handle = fopen($filePath, "a+");
                    $fileExists = true;
                } else {
                    $handle = fopen($filePath, "w+");
                }

                $lockResult = flock($handle, LOCK_EX);
                if (!$lockResult) {
                    $this->logger->warning("Lock Failed.");
                }

                if (!$fileExists) {
                    fputcsv($handle, $this->csvHeader($type));
                }

                foreach ($rowData as $row) {
                    if (isset($row['store_code'])) {
                        unset($row['store_code']);
                    }
                    if (!empty($row) && is_array($row)) {
                        if ($type == self::CSV_TYPE_HEADER) {
                            $verbAction = $row["transaction_type"] == UpdatePosData::PURCHASE_TRANS_TYPE ? "add" : "get back";
                            $this->csvLogger->info("Point $verbAction request created: " . $row["purchase_id"]);
                        }
                        fputcsv($handle, $row);
                    }
                }
                if ($lockResult) {
                    flock($handle, LOCK_UN);
                    $lockResult = false;
                }
                fclose($handle);
            } catch (\Exception $e) {
                if ($lockResult) {
                    flock($handle, LOCK_UN);
                }
            }

            // Convet EOL to \r\n
            $input = file_get_contents($filePath);
            if ($input) {
                $output = $this->convertEOL($input);
                file_put_contents($filePath, $output);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    public function createFileDirectory($fileDirectory)
    {
        if (!file_exists($this->filesystem->getAbsolutePath('/' . $fileDirectory))) {
            $this->directory->create($fileDirectory);
        }
    }

    public function generateRealStoresData($isRecover = false, $purchaseIds = [], $storeCode = 'sg')
    {
        $this->isRecover = $isRecover;
        $this->purchaseIds = $purchaseIds;
        $this->generateRealStoresDataToCsv("addpoint", $storeCode);
        $this->generateRealStoresDataToCsv("getbackpoint", $storeCode);
    }

    public function generateRealStoresDataToCsv($request = "addpoint", $storeCode = 'sg')
    {
        $this->logger->info("request: " . $request);
        $shopData = "";
        if ($request == "addpoint") {
            if ($this->isRecover) {
                $shopData = $this->shopItemsModel->loadAllShopDataForCpssCsvRecovery($this->purchaseIds);
            } else {
                $shopData = $this->shopItemsModel->loadAllShopDataForCpssCsv($this->getDateYesterday(), 'addpoint', $storeCode);
            }
        } elseif ($request == "getbackpoint") {
            if ($this->isRecover) {
                $shopData = $this->shopItemsModel->loadAllShopDataForCpssCsvRecovery($this->purchaseIds, "getbackpoint");
            } else {
                $shopData = $this->shopItemsModel->loadAllShopDataForCpssCsv($this->getDateYesterday(), "getbackpoint", $storeCode);
            }
        }

        if (!empty($shopData)) {
            foreach ($shopData as $k => $v) {
                $this->logger->info("Start: " . $v["purchase_id"]);
                $this->createCsvRealStore($k, $v["purchase_id"], $request, $storeCode);
                $this->logger->info("End: " . $v["purchase_id"]);
            }
        } else {
            $this->logger->critical("No Real Store records found.");
        }
    }

    // protected function addGetBackPointRequestData()
    // {
    //     $shopData = $this->shopItemsModel->loadAllShopDataForCpssCsv($this->getDateYesterday(), "getbackpoint");
    //     if (empty($shopData)) {
    //         $this->logger->critical("No Real Store records found.");
    //     } else {
    //         foreach ($shopData as $k => $v) {
    //             $this->createCsvRealStore($k, $v["purchase_id"], "getbackpoint");
    //         }
    //     }
    // }

    protected function createCsvRealStore($shopId, $purchaseIds, $request = "addpoint", $storeCode = 'sg')
    {
        try {
            $purchaseIdsForDetailsCsv = [];
            $entityIdsForUpdate = [];

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/realStoreCSV.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('==========================Create Real Store CSV Start============================');

            foreach (self::CSV_TYPES as $type) {
                $name = $this->getCurrentDate(date('Y-m-d H:i:s')) . '_' . $shopId;
                /*$sequenceNumber = str_pad(0, 3, 0, STR_PAD_LEFT);*/
                /*$sequenceNumber = '001';*/
                $sequenceNumber = str_pad(1, 3, "0", STR_PAD_LEFT);
                $filename = 'POS_' . $type . '_' . $name . '_' . $sequenceNumber . '.csv';
                $data = [];

                if ($type == self::CSV_TYPE_HEADER) {
                    $shopData = $this->shopItemsModel->loadShopByMultiplePurchaseId(
                        $purchaseIds,
                        ["purchase_id", "member_id", "purchase_date", "return_date", "shop_id", "pos_terminal_no", "transaction_type", "total_amount", "discount_amount", "tax_amount", "return_purchase_id", "return_total_amount", "return_discount_amount", "return_tax_amount", "store_code", "used_point"]
                    );
                    $i = 0;
                    $sPurchaseIds = [];

                    foreach ($shopData as $shopOrder) {
                        $logger->info('Original Data Purchase ID: ' . $shopOrder->getPurchaseId());
                        $returnData = [];
                        $usedPoints = $shopOrder->getUsedPoint();
                        $transactionType = $shopOrder->getTransactionType();
                        if (($transactionType == 1 || $transactionType == 3) && $request == "addpoint") {
                            $tType = $shopOrder->getTransactionType();
                            $shopOrder->setTransactionType(UpdatePosData::PURCHASE_TRANS_TYPE);
                            $shopOrder->setPurchaseDate(
                                $this->posHelper->convertTimezone($shopOrder->getPurchaseDate(), "UTC", "YmdHis")
                            );

                            $entityIdsForUpdate[] = [
                                "entity_id" => $shopOrder->getEntityId(),
                                "add_point_request_date" => $this->posHelper->convertTimezone($this->getCurrentDate(null, "Y-m-d H:i:s"), "UTC"),
                                "get_back_point_request_date" => null
                            ];
                            //echo '<pre>';print_r($shopOrder->getData());

                            $logger->info('Original Data');
                            $logger->info('ShopOrder Data: ' . print_r($shopOrder->getData(), true));

                            $totalAmount = $disAmount = $taxAmount = 0;
                            if ($tType == 3) {
                                $logger->info('Exchange Data');
                                $sItems = $this->shopItemsModel->loadAllShopItemsByPurchaseId(
                                    $purchaseIds,
                                    ["purchase_id", "sku", "qty", "subtotal_amount", "discount_amount", "tax_amount", "transaction_type"]
                                );
                                $logger->info('==========================Exchange Item Data Start============================');
                                foreach ($sItems as $iData) {
                                    if ($iData->getTransactionType() != 2 && $iData->getPurchaseId() == $shopOrder->getPurchaseId()) {
                                        $totalAmount += $iData->getSubtotalAmount();
                                        $disAmount += $iData->getDiscountAmount();
                                        $taxAmount += $iData->getTaxAmount();
                                        $logger->info('Exchange Item Data');
                                        $logger->info('ShopOrder Data: ' . print_r($iData->getData(), true));
                                    }
                                }
                                $logger->info('==========================Exchange Item Data End============================');
                            } else {
                                $logger->info('Purchase Data');
                            }
                            $purchaseId = $shopOrder->getPurchaseId();
                            $shopOrder = $shopOrder->getData();
                            unset($shopOrder["entity_id"]);
                            unset($shopOrder["return_date"]);
                            unset($shopOrder["return_total_amount"]);
                            unset($shopOrder["return_discount_amount"]);
                            unset($shopOrder["return_tax_amount"]);
                            unset($shopOrder["used_point"]);
                            $shopOrder['total_amount'] = ($tType == 3) ? round($totalAmount * 100) : round($shopOrder['total_amount'] * 100);
                            $shopOrder['discount_amount'] = ($tType == 3) ? round($disAmount * 100) : round($shopOrder['discount_amount'] * 100);
                            $shopOrder['tax_amount'] = ($tType == 3) ? round($taxAmount * 100) : round($shopOrder['tax_amount'] * 100);
                            $logger->info('Shop Data: ' . print_r($shopOrder, true));
                            if (!in_array($purchaseId, $sPurchaseIds)) {
                                $data[] = $shopOrder;
                                $logger->info('Shop Data Purchase ID: ' . $purchaseId);
                                $sPurchaseIds[] = $purchaseId;
                            }
                        } else {
                            $returnData = $shopOrder;
                            $shopOrder->setPurchaseDate(
                                $this->posHelper->convertTimezone($shopOrder->getPurchaseDate(), "UTC", "YmdHis")
                            );
                            $shopOrder->setReturnDate(
                                $this->posHelper->convertTimezone($shopOrder->getReturnDate(), "UTC", "YmdHis")
                            );

                            $eId = $shopOrder->getEntityId();
                            $pId = $shopOrder->getPurchaseId();
                            $rId = $shopOrder->getReturnPurchaseId();

                            if ($request == 'getbackpoint') {
                                $exOrder = $this->shopItemsModel->loadShopByPurchaseId($rId);
                                if ($exOrder && $exOrder->getId()) {
                                    $rId = $rId . 'R1';
                                }
                            }

                            $purchaseIdsForDetailsCsv[$pId] = $rId;
                            $transactionType = $shopOrder->getTransactionType();
                            if ($request == "addpoint") {
                                $shopOrder->setTransactionType(UpdatePosData::PURCHASE_TRANS_TYPE);
                                $shopOrder->setReturnPurchaseId("");

                                $entityIdsForUpdate[] = [
                                    "entity_id" => $eId,
                                    "add_point_request_date" => $this->posHelper->convertTimezone($this->getCurrentDate(null, "Y-m-d H:i:s"), "UTC"),
                                    "get_back_point_request_date" => null
                                ];

                                $logger->info('Original Data');
                                $logger->info('ShopOrder Data: ' . print_r($shopOrder->getData(), true));
                                $purchaseId = $shopOrder->getPurchaseId();
                                $shopOrder = $shopOrder->getData();

                                unset($shopOrder["entity_id"]);
                                unset($shopOrder["return_date"]);
                                $shopOrder['total_amount'] = round($shopOrder['return_total_amount'] * 100);
                                $shopOrder['discount_amount'] = round($shopOrder['return_discount_amount'] * 100);
                                $shopOrder['tax_amount'] = round($shopOrder['return_tax_amount'] * 100);
                                unset($shopOrder["return_total_amount"]);
                                unset($shopOrder["return_discount_amount"]);
                                unset($shopOrder["return_tax_amount"]);
                                unset($shopOrder["used_point"]);
                                if (!in_array($purchaseId, $sPurchaseIds)) {
                                    $data[] = $shopOrder;
                                    $logger->info('Purchase Data Of Return Purchase ID: ' . $purchaseId);
                                    $sPurchaseIds[] = $purchaseId;
                                }
                                $logger->info('Purchase Data Of Return');
                                $logger->info('Shop Data: ' . print_r($shopOrder, true));
                            } elseif ($request == "getbackpoint") {
                                $entityIdsForUpdate[] = [
                                    "entity_id" => $eId,
                                    "get_back_point_request_date" => $this->posHelper->convertTimezone($this->getCurrentDate(null, "Y-m-d H:i:s"), "UTC")
                                ];

                                $returnData->setPurchaseId($rId);
                                $returnData->setTransactionType(UpdatePosData::RETURN_TRANS_TYPE);
                                $returnData->setReturnPurchaseId($pId);

                                $logger->info('Original Return Data');
                                $logger->info('ShopOrder Data: ' . print_r($returnData->getData(), true));

                                $returnData = $returnData->getData();

                                unset($returnData["entity_id"]);
                                unset($returnData["purchase_date"]);
                                $returnData['total_amount'] = round($returnData['return_total_amount'] * 100);
                                $returnData['discount_amount'] = round($returnData['return_discount_amount'] * 100);
                                $returnData['tax_amount'] = round($returnData['return_tax_amount'] * 100);
                                unset($returnData["return_total_amount"]);
                                unset($returnData["return_discount_amount"]);
                                unset($returnData["return_tax_amount"]);
                                unset($returnData["used_point"]);
                                if (!in_array($rId, $sPurchaseIds)) {
                                    $logger->info('Return Data Purchase ID: ' . $rId);
                                    $data[] = $returnData;
                                    $sPurchaseIds[] = $rId;
                                }
                                $logger->info('Return Data');
                                $logger->info('Shop Data: ' . print_r($returnData, true));

                                if ($transactionType == 2 && $usedPoints > 0) {
                                    $logger->info('Transaction Type: ' . $transactionType);
                                    $logger->info('Original Purchase ID: ' . $pId);
                                    $logger->info('Return Purchase ID: ' . $shopOrder->getPurchaseId());
                                    $exchOrder = $this->shopItemsModel->loadShopByExchPurchaseId($pId);
                                    if ($exchOrder && $exchOrder->getId()) {
                                        $logger->info('Used Point API call');
                                        $logger->info('Purchase ID: ' . $pId);
                                        $logger->info('Member ID: ' . $shopOrder->getMemberId());
                                        $logger->info('Used Points: ' . $usedPoints);
                                        $responseTest = $this->cpssApiRequest->addPoint(
                                            $pId,
                                            $shopOrder->getMemberId(),
                                            $usedPoints,
                                            0,
                                            null,
                                            null,
                                            "",
                                            "",
                                            'G100002'
                                        );
                                        $logger->info('Used Points Response: ' . print_r($responseTest, true));
                                    }
                                }
                            }
                        }
                    }
                    $this->logger->info("filename: " . $filename);
                    $this->logger->info("data: " . print_r($data, true));

                    $filename = $this->getRealStoreCsvFileName($filename, $storeCode, $type, $name, $sequenceNumber);
                    $this->createCsv($filename, $data, self::CSV_TYPE_HEADER, $storeCode);
                    if (!empty($entityIdsForUpdate)) {
                        // Update Point Request Dates
                        $this->shopItemsModel->updateRequestDate(
                            $entityIdsForUpdate
                        );
                    }

                    $logger->info('Purchase Data Array: ' . print_r($sPurchaseIds, true));
                } elseif ($type == self::CSV_TYPE_DETAIL) {
                    $shopItems = $this->shopItemsModel->loadAllShopItemsByPurchaseId(
                        $purchaseIds,
                        ["purchase_id", "sku", "qty", "subtotal_amount", "discount_amount", "tax_amount", "transaction_type"]
                    );
                    $itemsCsvData = [];
                    $currentPurchaseId = "";
                    $itemsData2 = [];

                    foreach ($shopItems as $k => $itemData) {
                        if ($currentPurchaseId == "") {
                            $currentPurchaseId = $itemData->getPurchaseId();
                        } elseif ($currentPurchaseId != $itemData->getPurchaseId() && !empty($itemsData2)) {
                            foreach ($itemsData2 as $data2) {
                                $itemsCsvData[] = $data2;
                            }
                            $itemsData2 = [];
                        }

                        unset($itemData["item_id"]);
                        $id = $itemData->getPurchaseId();
                        $orderItemData = $itemData->getData();
                        unset($orderItemData["transaction_type"]);

                        $orderItemData['subtotal_amount'] = round($orderItemData['subtotal_amount'] * 100);
                        $orderItemData['discount_amount'] = round($orderItemData['discount_amount'] * 100);
                        $orderItemData['tax_amount'] = round($orderItemData['tax_amount'] * 100);

                        if ($request == "addpoint") {
                            $itemsCsvData[] = $orderItemData;
                        }

                        if (isset($purchaseIdsForDetailsCsv[$itemData->getPurchaseId()])) {
                            $orderItemData['purchase_id'] = $purchaseIdsForDetailsCsv[$itemData->getPurchaseId()];
                            $sOrder = $this->shopItemsModel->loadShopByPurchaseId($orderItemData['purchase_id']);

                            if (!$sOrder || $sOrder->getTransactionType() != 3) {
                                $itemsData2[] = $orderItemData;
                            }
                        }

                        $currentPurchaseId = $id;

                        if (array_key_last($shopItems->getData())  == $k) {
                            foreach ($itemsData2 as $data2) {
                                $itemsCsvData[] = $data2;
                            }
                            $itemsData2 = [];
                        }
                    }

                    $filename = $this->getRealStoreCsvFileName($filename, $storeCode, $type, $name, $sequenceNumber);

                    $this->createCsv($filename, $itemsCsvData, self::CSV_TYPE_DETAIL, $storeCode);
                } elseif ($type == self::CSV_TYPE_PRODUCT) {
                    $filename = $this->getRealStoreCsvFileName($filename, $storeCode, $type, $name, $sequenceNumber);
                    $this->createCsv($filename, [], self::CSV_TYPE_PRODUCT, $storeCode);
                }
            }

            $logger->info('==========================Create Real Store CSV End============================');
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    private function getRealStoreCsvFileName($filename, $storeCode, $type, $name, $sequenceNumber)
    {
        $fPath = $this->getFilePath('', $storeCode);
        $filePath = $fPath . $filename;

        $fName = 'POS_' . $type . '_' . $name . '_';
        $fileData = $this->getFileData($fName, '', $storeCode);

        if (!empty($fileData)) {
            $fileDate = $fileData['created_at'];
            $currentDate = $this->getCurrentDateWithTime();

            $minutes = round(abs(strtotime($fileDate) - strtotime($currentDate)) / 60);
            $configTime = $this->scopeConfig->getValue('sftp/pos/new_file_generation_time');
            $cTime = (!empty($configTime)) ? $configTime : 60;

            if ($fileData['is_file_uploaded'] || $minutes > $cTime) {
                $seqNumber = (int)$fileData['sequence_number'];
                $seqNumber++;
                $newSeqNumber =  str_pad($seqNumber, 3, "0", STR_PAD_LEFT);
                $filename = $fName . (string)$newSeqNumber . '.csv';
                $this->insertFileData($newSeqNumber, $filename, '', $storeCode);
            } else {
                $filename = $fileData['file_name'];
            }
        } else {
            $this->insertFileData($sequenceNumber, $filename, '', $storeCode);
        }

        return $filename;
    }

    protected function createCsvEc($shopId)
    {
        try {
            foreach (self::CSV_TYPES as $type) {
                $name = date('Ymd') . '_' . $shopId;
                $sequenceNumber = str_pad(0, 3, 0, STR_PAD_LEFT);
                $filename = 'POS_' . $type . '_' . $name . '_' . $sequenceNumber . '.csv';
                $data = "";
                $this->createCsv($filename, $data, $type);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    public function convertEOL($string, $to = "\r\n")
    {
        return preg_replace("/\r\n|\r|\n/", $to, $string);
    }

    public function csvHeader($type)
    {
        $row = self::HEADERS[$type];
        $row = $this->convertArrayToShiftjis($row);
        // foreach ($row as $key => $data) {
        //     $row[$key] = $data;
        // }

        // $header = $row;
        return $row;
    }

    public function getCurrentDate($date = null, $format = null)
    {
        $dateNow = $this->timezone->date(new \DateTime($date ?? 'now'));
        if ($format != null) {
            $dateNow = $dateNow->format($format);
        } else {
            $dateNow = $dateNow->format("Ymd");
        }

        return $dateNow;
    }

    private function getKrStoreCurrentDate($storeId)
    {
        $storeTimezone = $this->scopeConfig->getValue('general/locale/timezone', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $dateNow = $this->timezone->date(new \DateTime($date ?? 'now'));
        $dateNow = $dateNow->format("Ymd");
        $date = new \DateTime($dateNow); // Assuming original date is in UTC
        $date->setTimezone(new \DateTimeZone($storeTimezone));

        return $date->format('Ymd'); exit;
    }

    public function getCurrentDateWithTime($date = null, $format = null)
    {
        $dateNow = $this->timezone->date(new \DateTime($date ?? 'now'));
        if ($format != null) {
            $dateNow = $dateNow->format($format);
        } else {
            $dateNow = $dateNow->format("Y-m-d H:i:s");
        }

        return $dateNow;
    }

    public function getDateYesterday()
    {
        return $this->getCurrentDate(date('Y-m-d H:i:s', strtotime('-1 day')), "Y-m-d") . " 23:59:59";
    }

    public function getDateTodayTest()
    {
        return $this->getCurrentDate(date('Y-m-d', strtotime('now')), "Y-m-d") . " 23:59:59";
    }

    public function getCpssShopId($storeID = '')
    {
        if (!empty($storeID)) {
            $ecShopId = $this->scopeConfig->getValue(CpssHelper::CRM_CPSS_SHOP_ID, ScopeInterface::SCOPE_STORE, $storeID);
        } else {
            $ecShopId = $this->scopeConfig->getValue(CpssHelper::CRM_CPSS_SHOP_ID);
        }

        if (!$ecShopId) {
            return "58001";
        }
        return $ecShopId;
    }

    public function generateEcData($order, $orderId = null, $orderType = null, $shipment = false, $purchaseId = '')
    {
        $suffixType = null;
        try {
            $this->createFileDirectory($this->getPath());
            $cpssOrderItemsData = [];

            if ($orderType == "creditmemo") {
                $creditmemo = $order;
                $order = $creditmemo->getOrder();
                $creditmemoCount = $this->getCreditMemoCount($order->getId());
                $pointMultiplyBy = $this->cpssHelperData->getPointEarnedMultiplyBy($order->getStoreId());
                if ($order->getStatus() == "closed" && $creditmemoCount == 1) {
                    $suffixType = "A";
                    $suffix = "_RA";
                } else {
                    $suffixType = $creditmemoCount;
                    $suffix = "_R" . $creditmemoCount;
                }

                $totalWithoutTax = 0;
                $totalDiscount = 0;
                $totalTax = 0;
                $totalAmountWithoutTax =0;
                $isAddCreditMemoToSheet = false;

                $shipmentCollections = $order->getShipmentsCollection();
                $shipmentCount = $shipmentCollections->getSize();
                $creditmemoCollections = $order->getCreditmemosCollection();

                $credMemoItemCount = 0;
                $credMemoItemArray = [];
                $partialCredMemoItemArray = [];
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                if ($creditmemoCollections && $creditmemoCollections->getSize() > 0) {
                    foreach ($creditmemoCollections as $cMemo) {
                        if ($cMemo->getId() != $creditmemo->getId()) {
                            $cItemCollection = $objectManager->create(\Magento\Sales\Model\Order\Creditmemo\ItemFactory::class)->create()->getCollection();
                            $cItemCollection->addFieldToFilter('parent_id', ['eq' => $cMemo->getId()]);
                            $cItemCollection->addFieldToFilter('row_total', ['gt' => 0]);
                            $credMemoItemCount += $cItemCollection->getSize();
                            if ($cItemCollection && $cItemCollection->getSize() > 0) {
                                $cQty = 0;
                                foreach ($cItemCollection as $cItem) {
                                    $cOrderItem = $cItem->getOrderItem();
                                    $cOrderItemQty = (int)$cOrderItem->getQtyOrdered();
                                    $cQty = (int)$cItem->getQty();
                                    if ($cOrderItemQty == $cQty) {
                                        $credMemoItemArray[] = $cOrderItem->getId();
                                    } else {
                                        if (array_key_exists($cOrderItem->getId(), $partialCredMemoItemArray)) {
                                            $cQty += $partialCredMemoItemArray[$cOrderItem->getId()]['cMemoQty'];
                                        }
                                        $partialCredMemoItemArray[$cOrderItem->getId()] = [
                                            'totalQty' => $cOrderItemQty,
                                            'cMemoQty' => $cQty
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }

                $shipmentItemArray = [];
                if ($shipmentCount) {
                    $i = 1;
                    foreach ($shipmentCollections as $shipment) {
                        foreach ($shipment->getItemsCollection() as $key => $item) {
                            $shipmentItemArray[] = [
                                'orderItemId' => $item->getOrderItemId(),
                                'shippedQty' => (int)$item->getQty(),
                                'shipmentPrefix' => 'S' . $i];
                        }
                        $i++;
                    }
                }

                $itemSuffix = $credMemoItemCount + 1;
                foreach ($creditmemo->getAllItems() as $item) {
                    $orderItem = $item->getOrderItem();
                    if (
                        $item->getRowTotal() == null ||
                        (int)$orderItem->getQtyShipped() <= 0 ||
                        in_array($orderItem->getId(), $credMemoItemArray)
                    ) {
                        continue;
                    }

                    $orderQty = (int)$orderItem->getQtyOrdered();
                    $shipQty = $item->getQty();
                    $creditmemoDate = date("YmdHis", strtotime($creditmemo->getCreatedAt()));
                    $shippingQty = 1;
                    for ($i = 1; $i <= $shipQty; $i++) {
                        $itemRowTotalInclTax = ($orderItem->getRowTotalInclTax() / $orderQty) * 1;
                        $itemDiscount = ($orderItem->getDiscountAmount() / $orderQty) * 1;

                        $totalDiscountedPriceWithTax = $itemRowTotalInclTax - $itemDiscount;
                        $totalWithoutTax = $totalDiscountedPriceWithTax / (1 + ((int)$orderItem->getTaxPercent()/100));
                        $totalTax = $totalDiscountedPriceWithTax - $totalWithoutTax;
                        $totalDiscount = $itemDiscount;

                        $shipmentPrefix = '';
                        $shippedQty = 0;
                        foreach ($shipmentItemArray as $shippedItemKey => $shippedItem) {
                            if ($shippedItem['orderItemId'] == $orderItem->getId()) {
                                $shippedQty += $shippedItem['shippedQty'];

                                if (isset($partialCredMemoItemArray[$shippedItem['orderItemId']])) {
                                    $creditMemoQty = $partialCredMemoItemArray[$shippedItem['orderItemId']]['cMemoQty'];

                                    if ($creditMemoQty >= $shippedQty) {
                                        unset($shipmentItemArray[$shippedItemKey]);
                                        continue;
                                    } else {
                                        $shipmentPrefix = $shippedItem['shipmentPrefix'];
                                        break;
                                    }
                                } else {
                                    if ($orderQty >= $shippedQty) {
                                        $shipmentPrefix = $shipmentItemArray[$shippedItemKey]['shipmentPrefix'];
                                        if ($shippedItem['shippedQty'] == $shippingQty) {
                                            $shippingQty--;
                                            unset($shipmentItemArray[$shippedItemKey]);
                                            break;
                                        }
                                        $shippingQty++;
                                        break;
                                    }
                                }
                            }
                        }

                        $sourceSalesId = (!empty($shipmentPrefix)) ? $order->getIncrementId() . '_' . $shipmentPrefix : $order->getIncrementId();

                        $orderItems = [
                            $order->getIncrementId() . '_R' . $itemSuffix,                                     //purchaseId
                            $order->getCustomerId(),                                                //customerId
                            $creditmemoDate,//$this->getCurrentDate($creditmemo->getCreatedAt(), "YmdHis"),           //purchaseDate
                            $this->getCpssShopId($order->getStoreId()),                                                 //shopId
                            self::EC_POS_ID,                                                        //posTerminalNo
                            UpdatePosData::RETURN_TRANS_TYPE,                                       //transType
                            $totalWithoutTax <= 0 ? 0 : round($totalWithoutTax * $pointMultiplyBy),  //subTotal
                            $totalDiscount <= 0 ? 0 : round($totalDiscount * $pointMultiplyBy),                                 //discount
                            $totalTax <= 0 ? 0 : round($totalTax * $pointMultiplyBy),                                      //tax
                            $sourceSalesId                                                         //originOrderId
                        ];

                        $cpssOrderItemsData[] = $orderItems;
                        $itemSuffix++;
                    }
                    $isAddCreditMemoToSheet = true;
                }
                /*echo '<pre>';print_r($cpssOrderItemsData);exit;*/
                if (!$isAddCreditMemoToSheet) {
                    return $suffixType;
                }
            } else {
                $shipmentCount = count($order->getShipmentsCollection());
                $purchaseId = (!empty($purchaseId)) ? $purchaseId : $order->getIncrementId() . '_S' . $shipmentCount;
                $customerId = $order->getCustomerId();
                $purchaseDate = $this->getOrderDateById($order->getId());//$orderDate;//$this->getCurrentDate($order->getCreatedAt(), "YmdHis");
                $shopId = $this->getCpssShopId($order->getStoreId());
                $posTerminalNo = self::EC_POS_ID;
                $pointMultiplyBy = $this->cpssHelperData->getPointEarnedMultiplyBy($order->getStoreId());

                $transType = UpdatePosData::PURCHASE_TRANS_TYPE;
                if ($order->isCanceled()) {
                    $transType = UpdatePosData::RETURN_TRANS_TYPE;
                }

                $totalWithoutTax = 0;
                $totalDiscount = 0;
                $totalTax = 0;
                $totalAmountWithoutTax =0;

                if ($shipment && $shipment->getId()) {
                    foreach ($shipment->getItemsCollection() as $item) {
                        $orderItem = $item->getOrderItem();

                        if ($item->getRowTotal() == null && (int)$orderItem->getQtyShipped() <= 0) {
                            continue;
                        }

                        $orderQty = $orderItem->getQtyOrdered();
                        $shipQty = $item->getQty();
                        $itemRowTotalInclTax = $orderItem->getRowTotalInclTax();
                        $itemDiscount = $orderItem->getDiscountAmount();
                        if ($orderQty != $shipQty) {
                            $itemRowTotalInclTax = ($orderItem->getRowTotalInclTax() / $orderQty) * $shipQty;
                            $itemDiscount = ($orderItem->getDiscountAmount() / $orderQty) * $shipQty;
                        }

                        $totalDiscountedPriceWithTax = $itemRowTotalInclTax - $itemDiscount;
                        $totalWithoutTax = $totalDiscountedPriceWithTax / (1 + ((int)$orderItem->getTaxPercent()/100));
                        $totalTax += $totalDiscountedPriceWithTax - $totalWithoutTax;
                        $totalDiscount += $itemDiscount;
                        $totalAmountWithoutTax += $totalWithoutTax;

                        /*echo '-----Item Log Start-----';
                        echo 'Item Row Total Inc. Tax: '.$itemRowTotalInclTax.'<br>';
                        echo 'Item Discount: '.$itemDiscount.'<br>';
                        echo 'Total Discounted Price With Tax: '.$totalDiscountedPriceWithTax.'<br>';
                        echo 'Total Without Tax: '.$totalWithoutTax.'<br>';
                        echo '-----Item Log Start-----';*/
                    }
                }
                /*exit('After item list');*/
                /*foreach ($order->getAllVisibleItems() as $item) {
                    if ($item->getProductType() == "simple") {
                        continue;
                    }
                    $totalDiscountedPriceWithTax = $item->getRowTotalInclTax() - $item->getDiscountAmount();
                    $totalWithoutTax = $totalDiscountedPriceWithTax / (1 + ((int)$item->getTaxPercent()/100));
                    $totalTax += $totalDiscountedPriceWithTax - $totalWithoutTax;
                    $totalDiscount += $item->getDiscountAmount();
                    $totalAmountWithoutTax += $totalWithoutTax;
                }*/

                $subTotal = round($totalAmountWithoutTax * $pointMultiplyBy);
                $discount = round(($totalDiscount + $order->getNonProportionablePoint()) * $pointMultiplyBy);
                $tax = round($totalTax * $pointMultiplyBy);
                $originOrderId = "";

                $cpssOrderData = [
                    $purchaseId,
                    $customerId,
                    $purchaseDate,
                    $shopId,
                    $posTerminalNo,
                    $transType,
                    $subTotal <= 0 ? 0 : $subTotal,
                    $discount <= 0 ? 0 : $discount,
                    $tax <= 0 ? 0 : $tax,
                    $originOrderId
                ];
            }

            $fPath = $this->getFilePath($order->getStoreId());
            $currentTimezonDate = $this->getCurrentDate();
            if ($order->getStoreId() == 5) {
                $currentTimezonDate = $this->getKrStoreCurrentDate($order->getStoreId());
            }

            $sequenceNumber = '001';
            $filename = 'POS_HEADER_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . $sequenceNumber . '.csv';
            $filePath = $fPath . $filename;

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cpsspos.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('==========================Header File Debugging Start============================');
            $logger->info('FileName: ' . $filename);
            $logger->info('FilePath: ' . $filePath);

            $fName = 'POS_HEADER_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_';
            $fileData = $this->getFileData($fName, $order->getStoreId());
            $logger->info('File Name Without Sequence: ' . $fName);
            $logger->info('FilePath: ' . print_r($fileData, true));

            if (!empty($fileData)) {
                $filename = 'POS_HEADER_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . (string)$fileData['sequence_number'] . '.csv';
                $filePath = $fPath . $filename;
            }

            if (file_exists($filePath)) {
                if (!empty($fileData)) {
                    $fileDate = $fileData['created_at'];
                    $currentDate = $this->getCurrentDateWithTime();

                    $minutes = round(abs(strtotime($fileDate) - strtotime($currentDate)) / 60);
                    $configTime = $this->scopeConfig->getValue('sftp/pos/new_file_generation_time');
                    $cTime = (!empty($configTime)) ? $configTime : 60;

                    $logger->info('File Date: ' . $fileDate);
                    $logger->info('Current Date: ' . $currentDate);
                    $logger->info('Minutes: ' . $minutes);
                    $logger->info('Config Time: ' . $configTime);
                    $logger->info('New Config Time: ' . $cTime);
                    if ($fileData['is_file_uploaded'] || $minutes > $cTime) {
                        $nSeqNumber = (int)$fileData['sequence_number'];
                        $nSeqNumber++;
                        $newSeqNumber =  str_pad($nSeqNumber, 3, "0", STR_PAD_LEFT);
                        $filename = 'POS_HEADER_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . (string)$newSeqNumber . '.csv';
                        $this->insertFileData($newSeqNumber, $filename, $order->getStoreId());
                    } else {
                        $filename = $fileData['file_name'];
                    }

                    if (!empty($storeFolder)) {
                        $filePath = $fPath . '/' . $filename;
                    } else {
                        $filePath = $fPath . $filename;
                    }

                    $logger->info('NewFileName: ' . $filename);
                    $logger->info('NewFilePath: ' . $filePath);
                } else {
                    $this->insertFileData($sequenceNumber, $filename, $order->getStoreId());
                }
            } else {
                if (!empty($fileData)) {
                    $seqNumber = (int)$fileData['sequence_number'];
                    $seqNumber++;
                    $sequenceNumber =  str_pad($seqNumber, 3, "0", STR_PAD_LEFT);
                    $filename = 'POS_HEADER_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . (string)$sequenceNumber . '.csv';
                    $filePath = $fPath . $filename;
                }
                $this->insertFileData($sequenceNumber, $filename, $order->getStoreId());
            }

            $logger->info('==========================Header File Debugging End============================');

            $stream = new \SplFileObject($filePath, 'a');
            $stream->flock(LOCK_EX);
            $headers = self::HEADERS[self::CSV_TYPE_HEADER];
            $headers = $this->convertArrayToShiftjis($headers);

            if (!empty($cpssOrderData)) {
                $cpssOrderData = $this->convertArrayToShiftjis($cpssOrderData);
            }

            if (!empty($cpssOrderItemsData)) {
                $cpssOrderItemsData = $this->convertArrayToShiftjis($cpssOrderItemsData);
            }

            if (!$stream->getSize()) {
                $stream->fputcsv($headers);
            }

            if (!empty($cpssOrderItemsData)) {
                foreach ($cpssOrderItemsData as $items) {
                    $stream->fputcsv($items);
                }
            } else {
                $stream->fputcsv($cpssOrderData);
            }

            $stream->flock(LOCK_UN);
            $stream = null;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $suffixType;
    }

    /**
     * @param $storeId
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getFilePath($storeId = '', $storeCode = '')
    {
        if (empty($storeCode)) {
            $storeCodes = $this->cpssHelperData->getStoreCodes();
            $storeCode = (isset($storeCodes[$storeId])) ? $storeCodes[$storeId] : '';
        }

        $fPath = $this->directorySystem->getPath('var') . '/' . $this->getPath() . $storeCode . '/';
        if (!file_exists($fPath)) {
            mkdir($fPath, 0777, true);
        }

        return $fPath;
    }

    private function insertFileData($sequenceNumber, $fileName, $storeId = '', $storeCode = '')
    {
        if (empty($storeCode)) {
            $storeCodes = $this->cpssHelperData->getStoreCodes();
            $storeCode = (isset($storeCodes[$storeId])) ? $storeCodes[$storeId] : '';
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceConnection = $objectManager->create(\Magento\Framework\App\ResourceConnection::class);
        $connection  = $resourceConnection->getConnection();
        $tableName = $connection->getTableName('post_file_creation_time');
        $data = ['entity_id' => '', 'sequence_number' => $sequenceNumber, 'created_at' => $this->getCurrentDateWithTime(), 'file_name' => $fileName, 'store_code' => $storeCode];
        $connection->insert($tableName, $data);
    }

    private function getFileData($fileName, $storeId = '', $storeCode = '')
    {
        if (empty($storeCode)) {
            $storeCodes = $this->cpssHelperData->getStoreCodes();
            $storeCode = (isset($storeCodes[$storeId])) ? $storeCodes[$storeId] : '';
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceConnection = $objectManager->create(\Magento\Framework\App\ResourceConnection::class);
        $connection  = $resourceConnection->getConnection();
        $tableName = $connection->getTableName('post_file_creation_time');

        $query = 'SELECT * FROM ' . $tableName . ' where `file_name` like("' . $fileName . '%") AND store_code="' . $storeCode . '" ORDER BY `entity_id` DESC';
        return $resourceConnection->getConnection()->fetchRow($query);
    }

    public function generateEcItemsData($order, $incrementId, $orderType = null, $suffixType = null, $shipment = false, $purchaseId = '')
    {
        try {
            $this->createFileDirectory($this->getPath());

            $orderItems = [];

            $orderItemsData = $order->getAllVisibleItems();
            if ($orderType == "creditmemo") {
                $creditmemo = $order;
                $orderItemsData = $creditmemo->getAllItems();
                $isAddCreditMemoToSheet = false;
                $originalOrder = $creditmemo->getOrder();
                $creditmemoCollections = $originalOrder->getCreditmemosCollection();
                $pointMultiplyBy = $this->cpssHelperData->getPointEarnedMultiplyBy($originalOrder->getStoreId());

                $credMemoItemCount = 0;
                $credMemoItemArray = [];
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                if ($creditmemoCollections && $creditmemoCollections->getSize() > 0) {
                    foreach ($creditmemoCollections as $cMemo) {
                        if ($cMemo->getId() != $creditmemo->getId()) {
                            $cItemCollection = $objectManager->create(\Magento\Sales\Model\Order\Creditmemo\ItemFactory::class)->create()->getCollection();
                            $cItemCollection->addFieldToFilter('parent_id', ['eq' => $cMemo->getId()]);
                            $cItemCollection->addFieldToFilter('row_total', ['gt' => 0]);
                            $credMemoItemCount += $cItemCollection->getSize();
                            if ($cItemCollection && $cItemCollection->getSize() > 0) {
                                foreach ($cItemCollection as $cItem) {
                                    $cOrderItem = $cItem->getOrderItem();
                                    $cOrderItemQty = (int)$cOrderItem->getQtyOrdered();
                                    $cQty = (int)$cItem->getQty();
                                    if ($cOrderItemQty == $cQty) {
                                        $credMemoItemArray[] = $cOrderItem->getId();
                                    }
                                }
                            }
                        }
                    }
                }

                $itemSuffix = $credMemoItemCount + 1;
                foreach ($orderItemsData as $data) {
                    $orderItem = $data->getOrderItem();
                    if (
                        $data->getRowTotal() === null ||
                        (int)$orderItem->getQtyShipped() <= 0 ||
                        in_array($orderItem->getId(), $credMemoItemArray)
                    ) {
                        continue;
                    }

                    $orderQty = $orderItem->getQtyOrdered();
                    $shipQty = $data->getQty();

                    for ($i = 1; $i <= $shipQty; $i++) {
                        $itemRowTotalInclTax = ($orderItem->getRowTotalInclTax() / $orderQty) * 1;
                        $itemDiscountAmount = ($orderItem->getDiscountAmount() / $orderQty) * 1;

                        $totalDiscountedPriceWithTax = $itemRowTotalInclTax - $itemDiscountAmount;
                        $itemSubTotal = $totalDiscountedPriceWithTax / (1 + ((int)$orderItem->getTaxPercent()/100));

                        $tax = $totalDiscountedPriceWithTax - $itemSubTotal;
                        $itemDiscount = $itemDiscountAmount;
                        $itemTax = $tax;

                        $itemSubTotal = $itemSubTotal <= 0 ? 0 : $itemSubTotal;
                        $itemDiscount = $itemDiscount <= 0 ? 0 : $itemDiscount;
                        $itemTax = $itemTax <= 0 ? 0 : $itemTax;

                        $row['sales_id'] = $creditmemo->getOrder()->getIncrementId() . '_R' . $itemSuffix;
                        $row['product_id'] = $data->getSku();
                        $row['quantity_num'] = 1;
                        $row['sales_amount'] = round($itemSubTotal * $pointMultiplyBy);
                        $row['discount_amount'] = round($itemDiscount * $pointMultiplyBy);
                        $row['tax_amount'] = round($itemTax * $pointMultiplyBy);

                        $orderItems[] = $row;
                        $itemSuffix++;
                    }

                    $isAddCreditMemoToSheet = true;
                }

                if (!$isAddCreditMemoToSheet) {
                    return;
                }
            } else {
                $shipmentCount = count($order->getShipmentsCollection());
                $pointMultiplyBy = $this->cpssHelperData->getPointEarnedMultiplyBy($order->getStoreId());

                if ($shipment && $shipment->getId()) {
                    foreach ($shipment->getItemsCollection() as $item) {
                        $orderItem = $item->getOrderItem();

                        if ($item->getRowTotal() == null && (int)$orderItem->getQtyShipped() <= 0) {
                            continue;
                        }

                        $orderQty = $orderItem->getQtyOrdered();
                        $shipQty = $item->getQty();
                        $itemRowTotalInclTax = $orderItem->getRowTotalInclTax();
                        $itemDiscountAmount = $orderItem->getDiscountAmount();
                        if ($orderQty != $shipQty) {
                            $itemRowTotalInclTax = ($orderItem->getRowTotalInclTax() / $orderQty) * $shipQty;
                            $itemDiscountAmount = ($orderItem->getDiscountAmount() / $orderQty) * $shipQty;
                        }

                        $totalDiscountedPriceWithTax = $itemRowTotalInclTax - $itemDiscountAmount;
                        $itemSubTotal = $totalDiscountedPriceWithTax / (1 + ((int)$orderItem->getTaxPercent()/100));

                        $tax = $totalDiscountedPriceWithTax - $itemSubTotal;
                        $itemDiscount = $itemDiscountAmount;
                        $itemTax = $tax;

                        $itemSubTotal = $itemSubTotal <= 0 ? 0 : $itemSubTotal;
                        $itemDiscount = $itemDiscount <= 0 ? 0 : $itemDiscount;
                        $itemTax = $itemTax <= 0 ? 0 : $itemTax;

                        $row['sales_id'] = (!empty($purchaseId)) ? $purchaseId : $incrementId . '_S' . $shipmentCount;
                        $row['product_id'] = $orderItem->getSku();
                        $row['quantity_num'] = (int) $shipQty;
                        $row['sales_amount'] = round($itemSubTotal * $pointMultiplyBy);
                        $row['discount_amount'] = round($itemDiscount * $pointMultiplyBy);
                        $row['tax_amount'] = round($itemTax * $pointMultiplyBy);

                        $orderItems[] = $row;
                    }
                }

                /*foreach ($orderItemsData as $data) {
                    if ($data->getProductType() == 'simple') {
                        continue;
                    }

                    $totalDiscountedPriceWithTax = $data->getRowTotalInclTax() - $data->getDiscountAmount();
                    $itemSubTotal = $totalDiscountedPriceWithTax / (1 + ((int)$data->getTaxPercent()/100));

                    $tax = $totalDiscountedPriceWithTax - $itemSubTotal;
                    $itemDiscount = $data->getDiscountAmount();
                    $itemTax = $tax;

                    $itemSubTotal = $itemSubTotal <= 0 ? 0 : $itemSubTotal;
                    $itemDiscount = $itemDiscount <= 0 ? 0 : $itemDiscount;
                    $itemTax = $itemTax <= 0 ? 0 : $itemTax;

                    $row['sales_id'] = $incrementId . '_S' . $shipmentCount;;
                    $row['product_id'] = $data->getSku();
                    $row['quantity_num'] = (int) $data->getQtyOrdered();
                    $row['sales_amount'] = round($itemSubTotal * 100);
                    $row['discount_amount'] = round($itemDiscount * 100);
                    $row['tax_amount'] = round($itemTax * 100);

                    $orderItems[] = $row;
                }*/
            }

            $currentTimezonDate = $this->getCurrentDate();
            if ($order->getStoreId() == 5) {
                $currentTimezonDate = $this->getKrStoreCurrentDate($order->getStoreId());
            }

            $sequenceNumber = '001';
            $filename = 'POS_DETAIL_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . $sequenceNumber . '.csv';
            $fPath = $this->getFilePath($order->getStoreId());
            $filePath = $fPath . $filename;
            $fName = 'POS_DETAIL_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_';
            $fileData = $this->getFileData($fName, $order->getStoreId());

            if (!empty($fileData)) {
                $filename = 'POS_DETAIL_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . (string)$fileData['sequence_number'] . '.csv';
                $filePath = $fPath . $filename;
            }

            if (file_exists($filePath)) {
                if (!empty($fileData)) {
                    $fileDate = $fileData['created_at'];
                    $currentDate = $this->getCurrentDateWithTime();

                    $minutes = round(abs(strtotime($fileDate) - strtotime($currentDate)) / 60);
                    $configTime = $this->scopeConfig->getValue('sftp/pos/new_file_generation_time');
                    $cTime = (!empty($configTime)) ? $configTime : 60;

                    if ($fileData['is_file_uploaded'] || $minutes > $cTime) {
                        $nSeqNumber = (int)$fileData['sequence_number'];
                        $nSeqNumber++;
                        $newSeqNumber =  str_pad($nSeqNumber, 3, "0", STR_PAD_LEFT);
                        $filename = 'POS_DETAIL_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . (string)$newSeqNumber . '.csv';
                        $this->insertFileData($newSeqNumber, $filename, $order->getStoreId());
                    } else {
                        $filename = $fileData['file_name'];
                    }
                    $filePath = $fPath . $filename;
                } else {
                    $this->insertFileData($sequenceNumber, $filename, $order->getStoreId());
                }
            } else {
                if (!empty($fileData)) {
                    $seqNumber = (int)$fileData['sequence_number'];
                    $seqNumber++;
                    $sequenceNumber =  str_pad($seqNumber, 3, "0", STR_PAD_LEFT);
                    $filename = 'POS_DETAIL_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . (string)$sequenceNumber . '.csv';
                    $filePath = $fPath . $filename;
                }
                $this->insertFileData($sequenceNumber, $filename, $order->getStoreId());
            }

            $stream = new \SplFileObject($filePath, 'a');
            $stream->flock(LOCK_EX);
            $headers = self::HEADERS[self::CSV_TYPE_DETAIL];
            $headers = $this->convertArrayToShiftjis($headers);

            if (!empty($orderItems)) {
                $orderItems = $this->convertArrayToShiftjis($orderItems);
            }

            if (!$stream->getSize()) {
                $stream->fputcsv($headers);
            }

            foreach ($orderItems as $items) {
                $stream->fputcsv($items);
            }

            $stream->flock(LOCK_UN);
            $stream = null;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    public function getPath()
    {
        return $this->isRecover ? self::CPSS_CSV_RECOVERY_DIR : self::CPSS_CSV_DIR;
    }

    public function generateEcProductData($order, $purchaseId = '')
    {
        $currentTimezonDate = $this->getCurrentDate();
        if ($order->getStoreId() == 5) {
            $currentTimezonDate = $this->getKrStoreCurrentDate($order->getStoreId());
        }
        $this->createFileDirectory($this->getPath());
        $sequenceNumber = '001';
        $filename = 'POS_PRODUCT_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . $sequenceNumber . '.csv';
        $fPath = $this->getFilePath($order->getStoreId());
        $filePath = $fPath . $filename;
        $fName = 'POS_PRODUCT_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_';
        $fileData = $this->getFileData($fName, $order->getStoreId());

        if (!empty($fileData)) {
            $filename = 'POS_PRODUCT_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . (string)$fileData['sequence_number'] . '.csv';
            $filePath = $fPath . $filename;
        }

        if (file_exists($filePath)) {
            if (!empty($fileData)) {
                $fileDate = $fileData['created_at'];
                $currentDate = $this->getCurrentDateWithTime();

                $minutes = round(abs(strtotime($fileDate) - strtotime($currentDate)) / 60);
                $configTime = $this->scopeConfig->getValue('sftp/pos/new_file_generation_time');
                $cTime = (!empty($configTime)) ? $configTime : 60;

                if ($fileData['is_file_uploaded'] || $minutes > $cTime) {
                    $nSeqNumber = (int)$fileData['sequence_number'];
                    $nSeqNumber++;
                    $newSeqNumber =  str_pad($nSeqNumber, 3, "0", STR_PAD_LEFT);
                    $filename = 'POS_PRODUCT_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . (string)$newSeqNumber . '.csv';
                    $this->insertFileData($newSeqNumber, $filename, $order->getStoreId());
                } else {
                    $filename = $fileData['file_name'];
                }
                $filePath = $fPath . $filename;
            } else {
                $this->insertFileData($sequenceNumber, $filename, $order->getStoreId());
            }
        } else {
            if (!empty($fileData)) {
                $seqNumber = (int)$fileData['sequence_number'];
                $seqNumber++;
                $sequenceNumber =  str_pad($seqNumber, 3, "0", STR_PAD_LEFT);
                $filename = 'POS_PRODUCT_' . $currentTimezonDate . '_' . $this->getCpssShopId($order->getStoreId()) . '_' . (string)$sequenceNumber . '.csv';
                $filePath = $fPath . $filename;
            }
            $this->insertFileData($sequenceNumber, $filename, $order->getStoreId());
        }

        if (file_exists($filePath)) {
            return;
        } else {
            /*$stream = $this->directory->openFile($filePath);
            $stream->close();*/
            $stream = new \SplFileObject($filePath, 'a');
            $stream->flock(LOCK_EX);
            $headers = self::HEADERS[self::CSV_TYPE_PRODUCT];
            if (!$stream->getSize()) {
                $stream->fputcsv($headers);
            }
            $stream->flock(LOCK_UN);
            $stream = null;
        }
    }

    public function getCreditMemoCount($orderId)
    {
        $collection = $this->creditMemoCollection->create()
            ->addFieldToFilter('order_id', $orderId);
        return count($collection);
    }

    /**
     * @param array $data
     * @return array
     */
    public function convertArrayToShiftjis($data)
    {
        //Shift-JIS
        foreach ($data as $key => $value) {
            $key = mb_convert_encoding($key, "Shift-JIS", "UTF-8");
            $value = mb_convert_encoding($value, "Shift-JIS", "UTF-8");
            $data[$key] = $value;
        }
        return $data;
    }

    private function getOrderDateById($orderId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceConnection = $objectManager->create(\Magento\Framework\App\ResourceConnection::class);
        $tableName = $resourceConnection->getConnection()->getTableName('sales_order');

        $query = 'SELECT created_at FROM ' . $tableName . ' where entity_id = ' . $orderId;
        /**
         * Execute the query and fetch first email column only.
         */
        $result = $resourceConnection->getConnection()->fetchOne($query);
        $orderDate = date("YmdHis", strtotime($result));
        return $orderDate;
    }
}
