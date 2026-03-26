<?php

namespace Cpss\Pos\Cron;

use SplFileObject;

class UpdatePosDataRecovery extends UpdatePosData
{
    const CRM_SHOP_RECEIPT_TABLE = "sales_real_store_order";
    const CRM_SHOP_RECEIPT_PRODUCT_DETAILS_TABLE = "sales_real_store_order_item";
    const POS_CSV_LOCAL_DIR = BP . "/var/posRecoveryData/posData/";
    const POS_CSV_LOCAL_DIR_NOTSAVED = BP . "/var/posRecoveryData/posDataNotSaved/";

    const REMOVE_POS_CSV_LOCAL_DIR = BP . "/var/posRecoveryData";

    const DEFAULT_SFTP_PORT = 22;
    const XML_PATH = "sftp/pos/";

    const PURCHASE_TRANS_TYPE = "RCPT";
    const PURCHASE_TRANS_TYPE_VALUE = 1;
    const RETURN_TRANS_TYPE = "RETN";
    const RETURN_TRANS_TYPE_VALUE = 2;
    const CANCEL_TRANS_TYPE = "CNCL"; // same as RETN
    const RETURNCANCEL_TRANS_TYPE = "RECL"; // Cancellation of Return

    const CSV_TYPE_HEADER = "HEADER";
    const CSV_TYPE_DETAILS = "DETAIL";

    const POS_HEADER_COLS = [
        "購入ID", //purchase_id
        "顧客ID", //member_id
        "購入日時", //purchase_date or return_date
        "店ID", // store-id
        "担当者ID", // pos_terminal_no
        "伝票区分", // transaction_type
        "購入額合計", //totaL_amount
        "値引額合計", //discount_amount
        "消費税額合計", //tax_amount
        "元購入ID", //purchase_id and transaction_type
        "クレジットカード払い金額", //payment_method and order_comment
        "掛売金額", ////payment_method and order_comment
        "金券金額", //payment_method and order_comment
        "クーポン券金額", //payment_method and order_comment
        "金券B金額", //payment_method and order_comment
        "その他支払い金額", //payment_method and order_comment
    ];

    const POS_DETAILS_COLS = [
        "購入ID", //purchase_Id
        "商品ID", //sku
        "POS商品コード", //JAN code
        "数量", //qty
        "購入額", //subtotal_amount
        "値引額", //discount_amount
        "OTポイント値引額", //point discount amount
        "消費税額", // tax_amount
        "商品名", // product_name
        "カラー", //color
        "サイズ" // size
    ];

    const PAYMENT_TYPE_DEFINITION = [
        "01" => "現金",
        "02" => "クレジットカード",
        "03" => "交通系IC",
        "04" => "金券",
        "10" => "銀聯",
        "12" => "楽天Edy",
        "13" => "iD",
        "14" => "QUICPay",
        "15" => "WAON",
        "16" => "nanako",
        "Other" => "未定義"
    ];

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteFactory
     */
    protected $writeFactory;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    public function getHelper()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->create(\Cpss\Pos\Helper\Recovery::class);
    }

    public function getFileSystem()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->create(\Magento\Framework\Filesystem\Io\File::class);
    }

    public function execute($storeCode)
    {
        $this->writeFactory = $this->getHelper()->getWriteFactory();
        $this->directoryList = $this->getHelper()->getDirectoryList();
        $this->file = $this->getHelper()->getFile();
        return $this->process($storeCode);
    }

    protected function process($storeCode)
    {
        $path = $this->directoryList->getPath('var') . "/posRecoveryData";
        if (!$this->file->isExists($path)) {
            $this->logger->error(
                "Directory does not exists. Please check directory.",
                ["POS Path" => $path]
            );
            return;
        }

        $directory = $this->file->readDirectory($path);
        $fileList = [];
        foreach ($directory as $file) {
            $ext = $this->getFileExtension($file);
            if ($ext['ext'] == 'end' || $ext['ext'] == 'csv') {
                array_push($fileList, $file);
            }
        }

        $fileList = array_flip($fileList);
        unset($fileList['.']);
        unset($fileList['..']);
        ksort($fileList);

        if (!$fileList) {
            $this->logger->info("No files found in recovery directory.");
        } else {
            //Get end files for comparing filenames
            $endFiles = [];
            foreach ($fileList as $k => $v) {
                $ext = $this->getFileExtension($k);
                if ($ext['ext'] == 'end') {
                    //unset end files so that it willl not be processed in the next function
                    unset($fileList[$k]);
                    $endFiles[$ext['filename']] = 0;
                }
            }

            // $endFiles = array_flip($endFiles);

            return $this->processCsvFiles($fileList, $endFiles, $storeCode);
        }

        return [];
    }

    protected function processCsvFiles($fileList, $endFiles, $storeCode)
    {
        try {
            $csvData = [];
            $csvDataHeader = [];
            $csvDataDetails = [];
            $cpssCsvRecord = [];
            $processedFiles = [];
            $mailErrorFlag = false;
            $mailErrorMessage = "";

            $purchaseIds = [];
            foreach ($fileList as $fileName => $info) {
                // Check File Extension
                $ext = $this->getFileExtension($fileName);
                if ($ext['ext'] != "csv") {
                    continue;
                }


                if (!isset($endFiles[$ext['filename']])) {
                    $this->logger->error("End file not found for csv: " . $fileName);
                    continue;
                }

                //Check if end files, header and detail csv files are present
                $headerFile = "";
                $detailFile = "";
                if (strpos($fileName, strtolower(self::CSV_TYPE_DETAILS)) !== false) {
                    $headerFile = str_replace('detail_', '', $ext['filename']);
                    $detailFile = $ext['filename'];
                } else {
                    $headerFile = $ext['filename'];
                    $detailFile = str_replace('otsales_', 'otsales_detail_', $ext['filename']);
                }

                if (isset($endFiles[$headerFile]) && isset($fileList[$headerFile . '.csv']) &&
                    isset($endFiles[$detailFile]) && isset($fileList[$detailFile . '.csv'])
                ) {
                    if (!empty($processedFiles)) {
                        $flipProcFiles = array_flip($processedFiles);
                        if (isset($flipProcFiles[$detailFile])) {
                            //Skip DETAIL csv that was already added to csvData array
                            continue;
                        }
                    }

                    // Build Csv Data
                    $this->csvLogger->info("Get $fileName Host " . $this->usedSftpHost);
                    $posHeaderResult = $this->processPosHeaderData(
                        $this->readCsvFile($this->file->fileGetContents($headerFile . '.csv'))
                    );

                    if ($posHeaderResult["status"] != true || $posHeaderResult["errMessage"] != "" || $posHeaderResult["data"] == []) {
                        $mailErrorFlag = true;
                        $mailErrorMessage .= $posHeaderResult["errMessage"];
                    } else {
                        $csvDataHeader[] = $posHeaderResult["data"];
                    }

                    $this->csvLogger->info("Get $detailFile.csv Host " . $this->usedSftpHost);
                    $posDetailResult = $this->processPosProductsData(
                        $this->readCsvFile($this->file->fileGetContents($detailFile . '.csv'))
                    );

                    if ($posDetailResult["status"] != true || $posDetailResult["errMessage"] != "" || $posDetailResult["data"] == []) {
                        $mailErrorFlag = true;
                        $mailErrorMessage .= $posDetailResult["errMessage"];
                    } else {
                        $csvDataDetails[] = $posDetailResult["data"];
                    }

                    $processedFiles[] = $detailFile;
                } else {
                    $this->logger->critical(
                        "Please confirm that csv files (otsales*.csv, otsales_detail*.csv and end files) are complete.",
                        [$fileName]
                    );
                }
            }

            if ($mailErrorFlag) {
                $this->posMailer->sendEmail([], "validation_error", $mailErrorMessage);
                $this->logger->error("Validation Error!", ["mailError" => $mailErrorMessage]);
                return;
            }

            $headerData = [];
            if (!empty($csvDataHeader)) {
                array_walk(
                    $csvDataHeader,
                    function ($index, $value) use (&$headerData) {
                        $headerData = array_merge($headerData, array_values($index));
                    }
                );
            }

            $detailsData = [];
            if (!empty($csvDataDetails)) {
                array_walk(
                    $csvDataDetails,
                    function ($index, $value) use (&$detailsData) {
                        $detailsData = array_merge($detailsData, array_values($index));
                    }
                );
            }

            $csvData[self::CSV_TYPE_HEADER] = $headerData;
            $csvData[self::CSV_TYPE_DETAILS] = $detailsData;

            $isAllowedInsert = false;
            if (!empty($csvData[self::CSV_TYPE_HEADER]) && !empty($csvData[self::CSV_TYPE_DETAILS])) {
                // Both csvData should have data
                $isAllowedInsert = true;
            }

            if (!$isAllowedInsert) {
                $this->posMailer->sendEmail([], "validation_error", "Missing/Corrupt CSV Data.");
                $this->logger->error("Missing/Corrupt CSV Data.", [$csvData]);
            } else {
                $this->logger->info("Start merge POS data to DB.");
                //Record purchase ID that has return data
                $returnData = [];

                //Record RECL Data (these data should be inserted last)
                $reclRecords = [];
                //Insert the data for the Header CSV first
                if (isset($csvData[self::CSV_TYPE_HEADER])) {
                    $notSavedHeaderData = [];
                    $flippedRcptRecords = array_flip($this->rcptRecords);

                    foreach ($csvData[self::CSV_TYPE_HEADER] as $data) {
                        $returnSaveData = [];
                        $purchaseIds[] = $data['purchase_id'];
                        // Check if data is RECL (will determine if need to be added to CPSS CSV)
                        // RECL transaction type is 1
                        if (isset($data['RECL'])) {
                            unset($data['RECL']);
                            $reclRecords[] = $data;
                            continue;
                        }

                        $transType = self::PURCHASE_TRANS_TYPE;
                        $posPurchaseId = $data["purchase_id"];

                        if ($data["transaction_type"] == 2) {
                            if (!isset($flippedRcptRecords[$data["purchase_id"]])) {
                                $posData = $this->posdataFactory->create()->loadShopByPurchaseId($data["purchase_id"]);
                                if (!$posData->getEntityId()) {
                                    //Don't save data with no RCPT record
                                    $data["error_message"] = "Customer RETN or RECL products without purchase record";
                                    $this->logger->info("Customer RETN or RECL products without purchase record", [$data]);
                                    $notSavedHeaderData[] = $data;
                                    continue;
                                }
                            }

                            //Record purchase ID that has return data
                            $returnData[] = $data["return_purchase_id"];

                            //Update needed columns only (exclude other columns data)
                            $returnSaveData["purchase_id"] = $data["purchase_id"];
                            $returnSaveData["transaction_type"] = $data["transaction_type"];
                            $returnSaveData["return_purchase_id"] = $data["return_purchase_id"];
                            $returnSaveData["return_date"] = $data["return_date"];
                            $transType = self::RETURN_TRANS_TYPE;
                            $posPurchaseId = $data["purchase_id"];
                        }

                        //insert data
                        $saveData = $data["transaction_type"] == 2 ? $returnSaveData : $data;
                        $saved = $this->updateCrmShopReceipt($saveData);
                        if (!$saved["status"]) {
                            $data["error_message"] = $saved["message"];
                            $notSavedHeaderData[] = $data;
                            continue;
                        }
                        $this->csvLogger->info("Merged: $posPurchaseId $transType");
                        $cpssCsvRecord[self::CSV_TYPE_HEADER][$data["shop_id"]][] = $data;
                    }
                }

                //Insert the data for the Deatil CSV
                if (isset($csvData[self::CSV_TYPE_DETAILS])) {
                    $notSavedDetailsData = [];

                    //Get all purchase ID for deletion
                    $purchaseIdsForDelete = [];
                    foreach ($csvData[self::CSV_TYPE_DETAILS] as $data) {
                        $purchaseIdsForDelete[] = $data["purchase_id"];
                    }
                    $this->deleteProductDetailsData(array_unique($purchaseIdsForDelete));

                    $flippedReturnData = array_flip($returnData);
                    foreach ($csvData[self::CSV_TYPE_DETAILS] as $data) {
                        //set foreign key
                        $data["sales_real_store_order_id"] = $this->getColumnValueByPurchaseId(
                            $data["purchase_id"],
                            "entity_id"
                        );

                        // Get shop_id
                        $shopId = $this->extractDataFromPurchaseId($data["purchase_id"], "shop_id");


                        if (!$data["sales_real_store_order_id"]) {
                            if (isset($flippedReturnData[$data["purchase_id"]])) {
                                // Record purchase ID that has return data
                                $cpssCsvRecord[self::CSV_TYPE_DETAILS][$shopId][] = $data;
                            }

                            //ignore if foreign key is empty (probably RETN or CNCL case)
                            $this->logger->info("Ignore Data. Empty Foreign Key: sales_real_store_order_id ", $data);
                            $this->backupNotSavedData([$data], self::CSV_TYPE_DETAILS, true);
                        } else {
                            // insert data
                            $saved = $this->insertProductDetailsData($data);
                            if (!$saved["status"]) {
                                $data["error_message"] = $saved["message"];
                                $notSavedDetailsData[] = $data;
                                continue;
                            }

                            $cpssCsvRecord[self::CSV_TYPE_DETAILS][$shopId][] = $data;
                        }
                    }
                }

                if (!empty($reclRecords)) {
                    foreach ($reclRecords as $recl) {
                        try {
                            $pos = $this->posdataFactory->create();
                            $reclData = $pos->loadShopByReturnPurchaseId($recl["return_purchase_id"]);
                            $reclData->setTransactionType(1);
                            $reclData->save();
                            $this->csvLogger->info("Merged: " . $recl["purchase_id"] . " RECL Origin: " . $recl["return_purchase_id"]);
                        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                            $this->logger->error($e->getMessage(), ["RECL" => $recl["purchase_id"] . " RECL Origin: " . $recl["return_purchase_id"]]);
                        }
                    }
                }

                //send Mail for Not Saved Items
                $mergedPosData = [
                    "HEADER" => $notSavedHeaderData,
                    "DETAILS" => $notSavedDetailsData,
                    "IGNORED" => $this->ignoredData
                ];
                $this->posMailer->sendEmail($mergedPosData);

                // Remove sftp from POS SFTP server
                $this->removeCsvFromPosRecoveryData($fileList, $endFiles);

                // Generate CPSS Csv
                // $this->generateCpssCsvAfterPOS($cpssCsvRecord);

                return $purchaseIds;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $purchaseIds;
    }

    protected function removeCsvFromPosRecoveryData($fileList, $endFiles)
    {
        try {
            //Remove files from sftp server and Create a backup in $localDir
            if (!file_exists(self::POS_CSV_LOCAL_DIR)) {
                mkdir(self::POS_CSV_LOCAL_DIR);
            }

            $mergedFiles = array_keys(array_merge($fileList, $endFiles));
            $filesystem = $this->getFileSystem();

            foreach ($mergedFiles as $file) {
                $csvData = $file;
                $ext = $this->getFileExtension($file);

                if ($ext["ext"] == "csv") {
                    $file = trim(str_replace('/app/var/posRecoveryData/','',$file));
                    $localCsvFile = self::POS_CSV_LOCAL_DIR . date('Ymd_His_') . $file;
                    $filesystem->cp("/app/var/posRecoveryData/" . $file, $localCsvFile);
                    $this->file->fileGetContents($csvData, $localCsvFile);
                    $this->csvLogger->info("Delete " . $file . " HOST " . $this->usedSftpHost);
                    $this->file->deleteFile($csvData);
                    continue;
                }

                $csvEnd = $file . ".end";
                $this->file->deleteFile($csvEnd);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Backup Note Saved Data
     *
     * @param array $data
     * @param string $csvType
     * @param boolean $ignored
     * @return void
     */
    protected function backupNotSavedData($data, $csvType, $ignored = false)
    {
        // Create backup for not saved data
        if (!file_exists(self::POS_CSV_LOCAL_DIR_NOTSAVED)) {
            mkdir(self::POS_CSV_LOCAL_DIR_NOTSAVED);
        }

        switch ($csvType) {
            case self::CSV_TYPE_HEADER:
                if (!empty($data)) {
                    $localBackup = self::POS_CSV_LOCAL_DIR_NOTSAVED . date('Ymd_Hi00_');
                    if ($ignored) {
                        $localBackup .= 'POS_HEADER_IGNORED.csv';
                    } else {
                        $localBackup .= 'POS_HEADER.csv';
                    }

                    $this->logger->info($localBackup);
                    $this->writeToCsv($localBackup, self::POS_HEADER_COLS, $data);
                }
                break;
            case self::CSV_TYPE_DETAILS:
                if (!empty($data)) {
                    $localBackup = self::POS_CSV_LOCAL_DIR_NOTSAVED . date('Ymd_Hi00_');
                    if ($ignored) {
                        $localBackup .= 'POS_DETAIL_IGNORED.csv';
                    } else {
                        $localBackup .= 'POS_DETAIL.csv';
                    }

                    $this->writeToCsv($localBackup, self::POS_DETAILS_COLS, $data);
                }
                break;
        }
    }

    private function writeToCsv($localBackup, $headerCols, $data)
    {
        $file = new SplFileObject($localBackup, 'w');
        $file->flock(LOCK_EX);
        if (file_exists($localBackup)) {
            foreach ($data as $productData) {
                $file->fputcsv($productData, ",");
            }
        } else {
            $file->fputcsv($headerCols);
            foreach ($data as $productData) {
                $file->fputcsv($productData, ",");
            }
        }
        $file->flock(LOCK_UN);
        $file = null;
    }

    private function insertProductDetailsData($data)
    {
        $connection = $this->getResourceConnection();
        try {
            $connection->beginTransaction();
            $connection->insertOnDuplicate(self::CRM_SHOP_RECEIPT_PRODUCT_DETAILS_TABLE, $data);
            $connection->commit();
            // $connection->closeConnection();
            return [
                "status" => true,
                "message" => ""
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $data);
            $connection->rollBack();
            // $connection->closeConnection();
            return [
                "status" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    private function deleteProductDetailsData($purchaseIds)
    {
        $connection = $this->getResourceConnection();
        try {
            $sqlQuery = "DELETE FROM " . self::CRM_SHOP_RECEIPT_PRODUCT_DETAILS_TABLE . " WHERE purchase_id IN('" . implode("','", $purchaseIds) . "')";
            $this->logger->info($sqlQuery, $purchaseIds);
            $connection->query($sqlQuery);
            return true;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
    }

    private function updateCrmShopReceipt($data)
    {
        $connection = $this->getResourceConnection();
        try {
            $connection->beginTransaction();
            $connection->insertOnDuplicate(self::CRM_SHOP_RECEIPT_TABLE, $data);
            $connection->commit();
            // $connection->closeConnection();
            return [
                "status" => true,
                "message" => ""
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $connection->rollBack();
            // $connection->closeConnection();
            return [
                "status" => false,
                "message" => $e->getMessage()
            ];
        }
    }

}
