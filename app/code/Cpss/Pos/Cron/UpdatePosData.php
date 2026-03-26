<?php

namespace Cpss\Pos\Cron;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use SplFileObject;

class UpdatePosData
{
    const CRM_SHOP_RECEIPT_TABLE = "sales_real_store_order";
    const CRM_SHOP_RECEIPT_PRODUCT_DETAILS_TABLE = "sales_real_store_order_item";
    const POS_CSV_LOCAL_DIR = BP . "/var/posData/";
    const POS_CSV_LOCAL_DIR_NOTSAVED = BP . "/var/posDataNotSaved/";

    const DEFAULT_SFTP_PORT = 22;
    const XML_PATH = "sftp/pos/";

    const PURCHASE_TRANS_TYPE = "RCPT";
    const PURCHASE_TRANS_TYPE_VALUE = 1;
    const RETURN_TRANS_TYPE = "RETN";
    const RETURN_TRANS_TYPE_VALUE = 2;
    const EXCH_TRANS_TYPE = "EXCH";
    const EXCH_TRANS_TYPE_VALUE = 3;
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

    protected $resourceConnection;
    protected $logger;
    protected $sftp;
    protected $scopeConfig;
    protected $shopReceipt;
    protected $encryptor;
    protected $createCpssCsv;
    protected $posdataFactory;
    protected $posdataRessourceModel;
    protected $timezone;
    protected $csvLogger;
    protected $usedSftpHost = "";
    protected $posHelper;
    protected $posMailer;
    protected $ignoredData = [];
    protected $rcptRecords = [];

    protected $exchData = [];

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Cpss\Pos\Logger\Logger $logger,
        \Cpss\Crm\Model\ShopReceiptFactory $shopReceipt,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Cpss\Pos\Helper\CreateCsv $createCpssCsv,
        \Cpss\Pos\Model\PosDataFactory $posdataFactory,
        \Cpss\Pos\Model\ResourceModel\PosData $posdataRessourceModel,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Cpss\Pos\Logger\CsvLogger $csvLogger,
        \Cpss\Pos\Helper\Data $posHelper,
        \Cpss\Pos\Helper\Mail $posMailer,
        private \Magento\Framework\Filesystem\DirectoryList $directoryList,
        private \Magento\Framework\Filesystem\Driver\File $driverFile,
        public \OnitsukaTigerCpss\Crm\Helper\HelperData $helperData,
        protected \OnitsukaTigerCpss\PaymentList\Model\Source\PaymentOptions $paymentOptions
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->sftp = $sftp;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->shopReceipt = $shopReceipt;
        $this->encryptor = $encryptor;
        $this->createCpssCsv = $createCpssCsv;
        $this->posdataFactory = $posdataFactory;
        $this->posdataRessourceModel = $posdataRessourceModel;
        $this->timezone = $timezone;
        $this->csvLogger = $csvLogger;
        $this->posHelper = $posHelper;
        $this->posMailer = $posMailer;
        $this->directoryList =$directoryList;
        $this->driverFile = $driverFile;
    }

    public function __destruct()
    {
        if ($this->resourceConnection) {
            $this->resourceConnection->closeConnection();
        }
    }

    /**
     * Get Config Value in Admin
     *
     * @param string ("host","username","password","port","path") $path
     * @return string
     */
    public function getConfigValue($path)
    {
        if ($path == 'port') {
            if (!$this->scopeConfig->getValue(self::XML_PATH . $path)) {
                return self::DEFAULT_SFTP_PORT;
            }
        }
        return $this->scopeConfig->getValue(self::XML_PATH . $path);
    }

    /**
     * Connect to sftp
     *
     * param bool $userStandByServer
     * @return bool
     */
    public function connectSftp($useStandByServer = false)
    {
        try {
            if ($useStandByServer) {
                $auth = [
                    'host' => $this->getConfigValue("standby_host"),
                    'port' => $this->getConfigValue("standby_port"),
                    'username' => $this->getConfigValue("username")
                ];
            } else {
                $auth = [
                    'host' => $this->getConfigValue("host"),
                    'port' => $this->getConfigValue("port"),
                    'username' => $this->getConfigValue("username")
                ];
            }

            //Set sftp port (need to append to host using ':' )
            if (isset($auth['port']) && $auth['port'] != 22) {
                $auth['host'] .= ':' . $auth['port'];
            }

            $this->usedSftpHost = $auth["host"];
            $this->logger->info("SFTP AUTH", $auth);
            $methodAccess = $this->getConfigValue("method_access");

            if ($methodAccess == "key") {
                $key = $this->encryptor->decrypt($this->getConfigValue("private_key"));
                $rsa = new \phpseclib\Crypt\RSA($key);
                $rsa->loadKey($key);
                $auth['password'] = $rsa;
            } else {
                $auth['password'] = $this->getConfigValue("password");
            }

            $this->sftp->open($auth);
            return true;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
    }

    public function getResourceConnection($resourceName = null)
    {
        $connection = $resourceName != null ?
            $this->resourceConnection->getConnection($resourceName) :
            $this->resourceConnection->getConnection();
        return $connection;
    }

    public function execute($storeCode)
    {
        /*if ($this->connectSftp()) {
            $this->logger->info("Connected to server 1.");*/
        $this->process($storeCode);
        /*} elseif ($this->connectSftp(true)) {
            $this->logger->info("Connected to server 2.");
            $this->process();
        } else {
            $this->logger->error("Cannot connect to POS SFTP server.");
        }*/
    }

    protected function process($storeCode)
    {
        $path = $this->directoryList->getPath(DirectoryList::DIR_VAR) . '/crm/' . $storeCode . '/PosSalesFile/';
        //read just that single directory
        $fileList =  $this->driverFile->readDirectory($path);
        //read all folders
        //$fileList =  $this->driverFile->readDirectoryRecursively($path);

        /*echo '<pre>';
        print_r($paths);
        print_r($fileList);
        die;*/

        /*$sftpPath = $this->getConfigValue("path");
        $isDir = $this->sftp->cd($sftpPath);
        if (!$isDir) {
            $this->logger->error(
                "SFTP Directory does not exists. Please check directory.",
                ["POS Path" => $sftpPath]
            );
            return;
        }
        $fileList = $this->sftp->rawls();
        unset($fileList['.']);
        unset($fileList['..']);*/
        ksort($fileList);
        array_flip($fileList);

        if (!$fileList) {
            $this->logger->info("No files found in sftp server.");
        } else {
            //Get end files for comparing filenames
            $endFiles = [];
            $newFileList = [];
            foreach ($fileList as $k => $v) {
                $ext = $this->getFileExtension($v);
                if ($ext['ext'] == 'end') {
                    //unset end files so that it willl not be processed in the next function
                    unset($fileList[$k]);
                    $endFiles[$ext['filename']] = 0;
                } else {
                    $newFileList[$v] = $v;
                }
            }
            // $endFiles = array_flip($endFiles);
            $this->processCsvFiles($newFileList, $endFiles, $storeCode);
        }
    }

    public function getFileExtension($string)
    {
        $file = explode(".", $string);
        $ext = end($file);
        return [
            'filename' => $file[0],
            'ext' => $ext
        ];
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
            foreach ($fileList as $fileName => $info) {
                // Check File Extension
                $ext = $this->getFileExtension($fileName);
                //if ($ext['ext'] != "csv" || (int) $info["size"] <= 3) {
                if (strtolower($ext['ext']) != "csv" && strtolower($ext['ext']) != "CSV") {
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

                if (isset($endFiles[$headerFile]) && (isset($fileList[$headerFile . '.csv']) || isset($fileList[$headerFile . '.CSV'])) &&
                    isset($endFiles[$detailFile]) && (isset($fileList[$detailFile . '.csv']) || isset($fileList[$detailFile . '.CSV']))
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
                        $this->readCsvFile($headerFile . '.csv')
                    );

                    if ($posHeaderResult["status"] != true || $posHeaderResult["errMessage"] != "" || $posHeaderResult["data"] == []) {
                        $mailErrorFlag = true;
                        $mailErrorMessage .= $posHeaderResult["errMessage"];
                    } else {
                        $csvDataHeader[] = $posHeaderResult["data"];
                    }

                    $this->csvLogger->info("Get $detailFile.csv Host " . $this->usedSftpHost);
                    $posDetailResult = $this->processPosProductsData(
                        $this->readCsvFile($detailFile . '.csv')
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
            if (!empty($csvData[self::CSV_TYPE_DETAILS])) {
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
                            $returnSaveData = $data;
                            //Record purchase ID that has return data
                            $returnData[$data["purchase_id"]] = $data["return_purchase_id"];

                            //Update needed columns only (exclude other columns data)
                            $returnSaveData["purchase_id"] = $data["purchase_id"];
                            $returnSaveData["transaction_type"] = $data["transaction_type"];
                            $returnSaveData["return_purchase_id"] = $data["return_purchase_id"];
                            $returnSaveData["return_date"] = $data["return_date"];
                            $returnSaveData["return_total_amount"] = $data["return_total_amount"];
                            $returnSaveData["return_discount_amount"] = $data["return_discount_amount"];
                            $returnSaveData["return_tax_amount"] = $data["return_tax_amount"];
                            $returnSaveData["return_point_discount_amount"] = $data["return_point_discount_amount"];
                            $transType = self::RETURN_TRANS_TYPE;
                            $posPurchaseId = $data["purchase_id"];
                        }

                        //insert data
                        $saveData = $data["transaction_type"] == 2 ? $returnSaveData : $data;
                        $saveData['store_code'] = $storeCode;

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

                                $this->updateReturnItemStatus($flippedReturnData[$data["purchase_id"]], $data['sku']);
                                continue;
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
                $this->removeCsvFromPath($fileList, $endFiles);

                // Generate CPSS Csv
                // $this->generateCpssCsvAfterPOS($cpssCsvRecord);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    private function updateReturnItemStatus($purchaseId, $sku)
    {
        $connection = $this->getResourceConnection();

        $tableName = $connection->getTableName(self::CRM_SHOP_RECEIPT_PRODUCT_DETAILS_TABLE);
        $sql = "UPDATE $tableName SET `return_flg` = 1, `transaction_type` = 2 WHERE $tableName.`purchase_id` = '$purchaseId' AND `sku` = '$sku'";

        $connection->query($sql);
    }

    protected function removeCsvFromPath($fileList, $endFiles)
    {
        try {
            //Remove files from sftp server and Create a backup in $localDir
            if (!file_exists(self::POS_CSV_LOCAL_DIR)) {
                mkdir(self::POS_CSV_LOCAL_DIR);
            }

            $csvFile = $this->getConfigValue("path") . '/';
            $mergedFiles = array_keys(array_merge($fileList, $endFiles));
            foreach ($mergedFiles as $file) {
                $ext = $this->getFileExtension($file);
                if ($ext["ext"] == "csv") {
                    $this->csvLogger->info("Delete " . $file);
                    unlink($file);
                    continue;
                }

                $csvEnd = $file . ".end";
                unlink($csvEnd);
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

    /**
     * Validate Values
     *
     * @param  array $data
     * @param  int $rowNumber
     * @param  string $type
     * @return boolean
     */
    public function validateValues($data, $rowNumber, $type)
    {
        $flag = true;
        $errMessage = "";
        if ($type == self::CSV_TYPE_DETAILS) {
            $columnNames = self::POS_DETAILS_COLS;
            $requiredValues = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
            $numericValues = [];
            $purchaseIdValues = [0];
        } elseif ($type == self::CSV_TYPE_HEADER) {
            $columnNames = self::POS_HEADER_COLS;
            $requiredValues = [0, 2, 3, 4, 5, 6, 7, 8, 9];
            $numericValues = [];
            $purchaseIdValues = [0, 10];
        }

        $rowNumber += 1;
        $purchaseId = "";
        foreach ($data as $key => $value) {
            if ($key == 0) {
                $purchaseId = $value;
            }

            if (in_array($key, $requiredValues) && empty($value) && (!isset($value) && $value != 0)) {
                $message = __('%1 is a required value in %2 | Row #: %3', $columnNames[$key], $type, $rowNumber);
                $errMessage .= $purchaseId . " : " . $message . "<br/>";
                $this->logger->error($message);
                $flag = false;
                // break;
            }

            if ($type == self::CSV_TYPE_HEADER && $key == 5) {
                if ($value == self::PURCHASE_TRANS_TYPE || $value == self::RETURNCANCEL_TRANS_TYPE) {
                    $value = self::PURCHASE_TRANS_TYPE_VALUE;
                } elseif ($value == self::RETURN_TRANS_TYPE || $value == self::CANCEL_TRANS_TYPE) {
                    $value = self::RETURN_TRANS_TYPE_VALUE;
                }
            }

            /*if ($type == self::CSV_TYPE_HEADER && $key == 11 && !empty($value)) {
                $paymentData = explode(',', $value);
                foreach ($paymentData as $k) {
                    if (!isset(self::PAYMENT_TYPE_DEFINITION[$k])) {
                        $message = __('%1 must be one of the following: "01","02","03","04","10","12","13","14","15","16" or "Other" in %2 csv | Row #: %3, Value: %4', $columnNames[$key], $type, $rowNumber, $k);
                        $errMessage .= $purchaseId . " : " . $message . "<br/>";
                        $this->logger->error($message);
                        // $flag = false;
                        // break;
                    }
                }
            }*/

            if (in_array($key, $numericValues) && !$this->isNumeric($value)) {
                $message = __('%1 must be numeric value in %2 | Row #: %3', $columnNames[$key], $type, $rowNumber);
                $errMessage .= $purchaseId . " : " . $message . "<br/>";
                $this->logger->error($message);
                $flag = false;
                // break;
            }

            if (in_array($key, $purchaseIdValues)) {
                $result = (!empty($value)) ? $this->validatePurchaseIdFormat($value) : true;
                if ($result !== true) {
                    $message = __('%1 %2 in %3 | Row #: %4', $columnNames[$key], $result, $type, $rowNumber);
                    $errMessage .= $purchaseId . " : " . $message . "<br/>";
                    $this->logger->error($message);
                    $flag = false;
                    // break;
                }
            }
        }

        return [
            "status" => $flag,
            "errMessage" => $errMessage
        ];
    }

    /**
     * Check if value is numeric
     *
     * @param  string $value
     * @return bool
     */
    public function isNumeric($value)
    {
        return preg_match('/^[0-9]+$/', $value);
    }

    /**
     * Check if string is alpha numeric
     *
     * @param  string $string
     * @return bool
     */
    public function isAlphaNumeric($string)
    {
        return preg_match("/^[a-zA-Z0-9_]*$/", $string);
    }

    public function validatePurchaseIdFormat($purchaseId)
    {
        $breakDown = explode("_", $purchaseId);
        $count = count($breakDown);
        if ($count != 4) {
            return __('%1 Invalid purchaseId format: 購入日付(8桁)_店舗ID(5桁)_POSレジ端末No.(4桁)_レシート番号(8桁)', $purchaseId);
        }

        if (!$this->dateFormat($breakDown[0])) {
            return __('%1 Invalid Date format: YYYYMMDD', $breakDown[0]);
        }

        if (strlen($breakDown[1]) != 5 || strlen($breakDown[2]) != 4 || strlen($breakDown[3]) != 8) {
            return __('%1 Invalid purchaseId section length: 購入日付(8桁)_店舗ID(5桁)_POSレジ端末No.(4桁)_レシート番号(8桁)', $purchaseId);
        }

        return true;
    }

    /**
     * Validate Date Format
     *
     * @param  string $date
     * @param  string $format
     * @return void
     */
    public function dateFormat($date, $format = 'Ymd')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return ($d && $d->format($format) === $date) ? true : false;
    }

    /**
     * Process Details Csv Data
     *
     * @param array $csv
     * @return array
     */
    public function processPosProductsData($csv)
    {
        $productDetails = [];
        $validationErrMessage = "";

        foreach ($csv as $key => $data) {
            $validationResult = $this->validateValues($data, $key, self::CSV_TYPE_DETAILS);
            if ($validationResult["status"]) {
                $returnFlag = 0;
                if ($data[1] == self::RETURN_TRANS_TYPE) {
                    $returnFlag = 1;
                }

                $sku = $data[2];

                if ($data[1] == self::RETURN_TRANS_TYPE) {
                    $transactionType = 3;
                    $sku = $data[2] . '_RETN';
                } elseif ($data[1] == self::EXCH_TRANS_TYPE) {
                    $transactionType = 3;
                    $sku = $data[2] . '_RETN';
                } else {
                    $transactionType = 1;
                }

                if (!empty($this->exchData) && in_array($data[0], $this->exchData)) {
                    $returnFlag = 1;
                    $transactionType = 2;
                }

                $productDetails[] = [
                    "sales_real_store_order_id" => "",
                    "purchase_id" => $data[0],
                    "sku" => $sku,
                    "jan_code" => $data[3],
                    "qty" => $data[4],
                    "subtotal_amount" => $data[5] - $data[8],
                    "discount_amount" => $data[6],
                    "tax_amount" => $data[8],
                    "product_name" => $data[9],
                    "color" => $data[10],
                    "size" => $data[11],
                    "return_flg" => $returnFlag,
                    "transaction_type" => $transactionType
                ];
            } else {
                $validationErrMessage .= $validationResult["errMessage"];
                $this->logger->error("Validation Failed.", [$data]);
            }
        }

        return [
            "status" => $validationErrMessage == "" ? true : false,
            "data" => $validationErrMessage == "" ? $productDetails : [],
            "errMessage" => $validationErrMessage
        ];
    }

    /**
     * Process Header Csv Data
     *
     * @param array $csv
     * @return array
     */
    public function processPosHeaderData($csv)
    {
        $productHeader = [];
        $validationErrMessage = "";

        foreach ($csv as $key => $data) {
            $validationResult = $this->validateValues($data, $key, self::CSV_TYPE_HEADER);

            if ($validationResult["status"]) {
                $tempData = [];
                if (($data[5] == self::RETURN_TRANS_TYPE || $data[5] == self::RETURNCANCEL_TRANS_TYPE || $data[5] == self::CANCEL_TRANS_TYPE) && empty($data[10])) {
                    $this->ignoredData[] = $data[0];
                    $this->logger->info("Customer RETN or RECL products without purchase receipt", [$data]);
                    continue;
                }

                if ($data[5] == self::RETURNCANCEL_TRANS_TYPE) {
                    // If RECL only update transaction type to 1
                    $tempData["purchase_id"] = $data[0];
                    $tempData["return_purchase_id"] = $data[10];
                    $tempData["transaction_type"] = self::PURCHASE_TRANS_TYPE_VALUE;
                    $tempData["shop_id"] = $this->extractDataFromPurchaseId($tempData["purchase_id"], "shop_id");
                    $tempData["RECL"] = 1;
                    $productHeader[] = $tempData;
                    continue;
                }

                $tempData["purchase_id"] = $data[0];
                $tempData["shop_id"] = $data[3];
                $tempData["pos_terminal_no"] = str_pad($data[4], 4, 0, STR_PAD_LEFT);
                $tempData["total_amount"] = $data[6] - $data[9];
                $tempData["discount_amount"] = $data[7];
                $tempData["tax_amount"] = $data[9];
                $tempData["payment_method"] = "-"; //Always "-"
                $tempData["order_comment"] = "";
                $tempData["point_discount_amount"] = $data[8];
                $tempData["receipt_no"] = $this->extractDataFromPurchaseId($data[0], "receipt_no");

                if (!empty($data[1])) {
                    $tempData["member_id"] = $data[1];
                    $tempData["guest_purchase_flg"] = 0;
                } else {
                    $memberId = $this->getColumnValueByPurchaseId($data[0], "member_id");
                    $tempData["guest_purchase_flg"] = $memberId != "" ? 0 : 1;
                }

                $tempData["order_comment"] .= "Payment Methods:\n";
                //check values for payment method and order comment columns
                if (isset($data[11])) {
                    if (empty($data[11])) {
                        // full point payment
                        //$tempData["order_comment"] .= "(全額ポイント支払い)";
                    } else {
                        $methodTitleArray = [];
                        if (!empty($data[11])) {
                            $methods = explode(' ', $data[11]);
                            $methodOptions = $this->paymentOptions->toArray();
                            foreach ($methods as $method) {
                                if (array_key_exists(trim($method), $methodOptions)) {
                                    $methodTitleArray[] = $method . ': ' . $methodOptions[$method];
                                }
                            }
                        }

                        $methodTitle = '';
                        if (!empty($methodTitleArray)) {
                            $methodTitle = implode(', ', $methodTitleArray);
                        }
                        $tempData["order_comment"] .= $methodTitle;
                    }
                }

                if ($data[5] == self::RETURN_TRANS_TYPE || $data[5] == self::CANCEL_TRANS_TYPE) {
                    $tempData["shop_id"] = ($data[5] == self::CANCEL_TRANS_TYPE) ? $this->extractDataFromPurchaseId($data[0], "shop_id") : $this->extractDataFromPurchaseId($data[10], "shop_id");
                    $tempData["purchase_id"] = $data[10];
                    $tempData["transaction_type"] = self::RETURN_TRANS_TYPE_VALUE;
                    $tempData["return_purchase_id"] = $data[0];
                    $tempData["return_date"] = $this->posHelper->convertTimezone($data[2], "UTC");
                    $tempData["return_total_amount"] = $data[6] - $data[9];
                    $tempData["return_discount_amount"] = $data[7];
                    $tempData["return_tax_amount"] = $data[9];
                    $tempData["return_point_discount_amount"] = $data[8];
                    // $tempData["canceled_used_point"] = $data[7];
                } elseif ($data[5] == self::EXCH_TRANS_TYPE) {
                    $tempData["exch_purchase_id"] = $data[10];
                    $tempData["transaction_type"] = self::EXCH_TRANS_TYPE_VALUE;
                    $tempData["exch_date"] = $this->posHelper->convertTimezone($data[2], "UTC");
                    $tempData["purchase_date"] = $this->posHelper->convertTimezone($data[2], "UTC");
                    $tempData["return_purchase_id"] = null;
                    $this->rcptRecords[] = $tempData["purchase_id"];

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $realStoreOrder = $objectManager->create(\Cpss\Crm\Model\ShopReceipt::class);
                    $realStoreOrder->loadByPurchaseId($data[10]);
                    if ($realStoreOrder && $realStoreOrder->getId()) {
                        if ($realStoreOrder->getTotalAmount() > 0) {
                            $realStoreOrder->setReturnDate($tempData["exch_date"]);
                            $realStoreOrder->setReturnTotalAmount($realStoreOrder->getTotalAmount());
                            $realStoreOrder->setReturnDiscountAmount($realStoreOrder->getDiscountAmount());
                            $realStoreOrder->setReturnTaxAmount($realStoreOrder->getTaxAmount());
                            $realStoreOrder->setReturnPurchaseId($tempData["purchase_id"]);
                            $realStoreOrder->setTransactionType(2);
                            $realStoreOrder->save();
                            $realStoreItemFactory = $objectManager->create(\Cpss\Pos\Model\PosData::class)->getCollection();
                            $realStoreItems = $realStoreItemFactory->addFieldToFilter('sales_real_store_order_id', ['eq' => $realStoreOrder->getId()]);
                            if ($realStoreItems && $realStoreItems->getSize() > 0) {
                                foreach ($realStoreItems as $realStoreItem) {
                                    $this->updateReturnItemStatus($realStoreItem->getPurchaseId(), $realStoreItem->getSku());
                                }
                            }
                        } else {
                            foreach ($productHeader as $key => $sOrderData) {
                                if ($sOrderData['purchase_id'] == $tempData["exch_purchase_id"]) {
                                    $sOrderData["transaction_type"] = self::RETURN_TRANS_TYPE_VALUE;
                                    $sOrderData["return_purchase_id"] = $tempData["purchase_id"];
                                    $sOrderData["return_date"] = $tempData["exch_date"];
                                    $sOrderData["return_total_amount"] = $sOrderData['total_amount'];
                                    $sOrderData["return_discount_amount"] = $sOrderData['discount_amount'];
                                    $sOrderData["return_tax_amount"] = $sOrderData['tax_amount'];
                                    $sOrderData["return_point_discount_amount"] = $sOrderData['point_discount_amount'];
                                    $productHeader[$key] = $sOrderData;
                                }
                            }
                            $this->exchData[] = $tempData["exch_purchase_id"];
                        }
                    } else {
                        foreach ($productHeader as $key => $sOrderData) {
                            if ($sOrderData['purchase_id'] == $tempData["exch_purchase_id"]) {
                                $sOrderData["transaction_type"] = self::RETURN_TRANS_TYPE_VALUE;
                                $sOrderData["return_purchase_id"] = $tempData["purchase_id"];
                                $sOrderData["return_date"] = $tempData["exch_date"];
                                $sOrderData["return_total_amount"] = $sOrderData['total_amount'];
                                $sOrderData["return_discount_amount"] = $sOrderData['discount_amount'];
                                $sOrderData["return_tax_amount"] = $sOrderData['tax_amount'];
                                $sOrderData["return_point_discount_amount"] = $sOrderData['point_discount_amount'];
                                $productHeader[$key] = $sOrderData;
                            }
                        }
                        $this->exchData[] = $tempData["exch_purchase_id"];
                    }
                } else {
                    $tempData["transaction_type"] = self::PURCHASE_TRANS_TYPE_VALUE;
                    $tempData["return_purchase_id"] = null;
                    $tempData["purchase_date"] = $this->posHelper->convertTimezone($data[2], "UTC");
                    // $tempData["used_point"] = $data[7];

                    $this->rcptRecords[] = $tempData["purchase_id"];
                }
                $productHeader[] = $tempData;
            } else {
                $validationErrMessage .= $validationResult["errMessage"];
                $this->logger->error("Validation Failed.", [$data]);
            }
        }

        return [
            "status" => $validationErrMessage == "" ? true : false,
            "data" => $validationErrMessage == "" ? $productHeader : [],
            "errMessage" => $validationErrMessage
        ];
    }

    /**
     * Get Data (Converts content to array)
     *
     * @param  mixed $content
     * @return array
     */
    public function readCsvFile($content)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $file = $objectManager->create(\Magento\Framework\Filesystem\Driver\File::class);
        $csv = $objectManager->create(\Magento\Framework\File\Csv::class);

        $data = [];
        if ($file->isExists($content)) {
            $csv->setDelimiter(",");
            $data = $csv->getData($content);
        }

        return $data;

        /*$lineBreak = "\n";
        $delimiter = ",";
        $row = str_getcsv($content, $lineBreak);
        $row[0] = $this->removeBom($row[0]);
        $length = count($row);



        $data = [];
        for ($i = 0; $i < $length;) {
            $rowData = str_getcsv($row[$i], $delimiter);
            $data[] = $rowData;
            $i++;
        }

        return $data;*/
    }

    public function getColumnValueByPurchaseId($purchaseId, $column)
    {
        $connection = $this->getResourceConnection();
        $select = $connection->select()->from(
            self::CRM_SHOP_RECEIPT_TABLE,
            $column
        )->where('purchase_id = :purchaseId');
        $bind = [':purchaseId' => (string) $purchaseId];

        $result = $connection->fetchOne($select, $bind);

        if (!$result) {
            return "";
        }

        return $result;
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

    protected function generateCpssCsvAfterPOS($cpssCsvRecord)
    {
        $sequenceNumber = str_pad(0, 3, 0, STR_PAD_LEFT);
        if (!empty($cpssCsvRecord[self::CSV_TYPE_HEADER]) && !empty($cpssCsvRecord[self::CSV_TYPE_DETAILS])) {
            foreach ($cpssCsvRecord[self::CSV_TYPE_HEADER] as $k => $record) {
                $rowHeaderData = [];
                $name = $this->createCpssCsv->getCurrentDate() . '_' . $k;
                $filename = "POS_" . self::CSV_TYPE_HEADER . '_' . $name . '_' . $sequenceNumber . '.csv';
                foreach ($record as $kRecord => $vRecord) {
                    $memberId = $this->getColumnValueByPurchaseId(
                        $vRecord['purchase_id'],
                        "member_id"
                    );
                    $rowHeaderData[] = [
                        $vRecord['transaction_type'] == 1 ? $vRecord['purchase_id'] : $vRecord['return_purchase_id'],
                        $memberId,
                        $vRecord['purchase_date'] ?? $vRecord['return_date'],
                        $vRecord['shop_id'],
                        $vRecord['pos_terminal_no'] ?? "",
                        $vRecord['transaction_type'] == 2 ? self::RETURN_TRANS_TYPE : self::PURCHASE_TRANS_TYPE,
                        $vRecord['total_amount'] - $vRecord['tax_amount'],
                        $vRecord['discount_amount'],
                        $vRecord['tax_amount'],
                        $vRecord['transaction_type'] == 1 ? "" : $vRecord['purchase_id']
                    ];
                }
                $this->createCpssCsv->createCsv($filename, $rowHeaderData, self::CSV_TYPE_HEADER);
            }

            foreach ($cpssCsvRecord[self::CSV_TYPE_DETAILS] as $k => $record) {
                $rowDetailData = [];
                $name = $this->createCpssCsv->getCurrentDate() . '_' . $k;
                $filenameDetails = "POS_" . self::CSV_TYPE_DETAILS . '_' . $name . '_' . $sequenceNumber . '.csv';
                foreach ($record as $kRecord => $vRecord) {
                    $rowDetailData[] = [
                        $vRecord['purchase_id'],
                        $vRecord['sku'],
                        $vRecord['qty'],
                        $vRecord['subtotal_amount'] - $vRecord['tax_amount'],
                        $vRecord['discount_amount'],
                        $vRecord['tax_amount']
                    ];
                }
                $this->createCpssCsv->createCsv($filenameDetails, $rowDetailData, self::CSV_TYPE_DETAILS);

                //Create POS PRODUCT CSV
                $filenameProduct = str_replace(
                    self::CSV_TYPE_DETAILS,
                    $this->createCpssCsv::CSV_TYPE_PRODUCT,
                    $filenameDetails
                );
                $this->createCpssCsv->createCsv($filenameProduct, "", $this->createCpssCsv::CSV_TYPE_PRODUCT);
            }
        }
    }

    public function removeBom($str)
    {
        $headStr = strtolower(substr($str, 0, 4)); //strtolower() is no need, but to use for safe.
        $remainStr = substr($str, 4);
        $bom = ["/^feff/", "/^fffe/", "/^efbbbf/", "/^0000feff/", "/^fffe0000/"];
        //Binary search and replace
        $headStr = hex2bin(preg_replace($bom, '', bin2hex($headStr)));

        return $headStr . $remainStr;
    }

    public function extractDataFromPurchaseId($purchaseId, $dataField)
    {
        preg_match("/^([0-9]{8})_([a-zA-Z0-9]{6})_([0-9]{5})_([0-9]{10})$/", $purchaseId, $extractedData);
        $data = "";

        if ($dataField == "purchase_date") {
            $data = $extractedData[1];
        } elseif ($dataField == "shop_id") {
            $data = $extractedData[2];
        } elseif ($dataField == "pos_terminal_no") {
            $data = $extractedData[3];
        } elseif ($dataField == "receipt_no") {
            $data = $extractedData[4];
        }
        return $data;
    }
}
