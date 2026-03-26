<?php

namespace Cpss\Pos\Cron;

use Cpss\Crm\Helper\SftpHelper;
use Cpss\Pos\Helper\CreateCsv;
use Cpss\Pos\Logger\CsvLogger;
use Cpss\Pos\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class CpssTransferFiles
{
    const CPSS_OUTBOUND_PATH = "sftp/cpss/out_path";
    const CPSS_INBOUND_PATH = "sftp/cpss/in_path";
    const INVALID_DIR = "Please verify that dir is valid, accessible and has correct premissions";

    protected $createCsv;
    protected $sftpHelper;
    protected $dir;
    protected $logger;
    protected $scopeConfig;
    protected $csvLogger;
    protected $usedSftpServer = "";
    protected $isRecover;

    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\HelperData
     */
    protected $cpssHelperData;

    public function __construct(
        CreateCsv $createCsv,
        SftpHelper $sftpHelper,
        DirectoryList $dir,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        CsvLogger $csvLogger,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $cpssHelperData
    ) {
        $this->createCsv = $createCsv;
        $this->sftpHelper = $sftpHelper;
        $this->dir = $dir;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->csvLogger = $csvLogger;
        $this->cpssHelperData = $cpssHelperData;
    }

    public function executeEc($storeCode, $isRealStore = false)
    {
        $this->execute($isRealStore, $storeCode);
    }

    public function executeRealStore($isRecover = false)
    {
        $this->isRecover = $isRecover;
        $this->execute(true);
    }

    protected function execute($isRealStore, $sCode = '')
    {
        $storeCodes = $this->cpssHelperData->getStoreCodes();
        foreach ($storeCodes as $storeId => $storeCode) {
            $filesToUpload = [];
            $dateYesterday = $this->getDateYesterday();
            $csvDir = $this->getCsvDir() . $storeCode . '/';
            $cpssOutBound = $this->getConfigValue(self::CPSS_OUTBOUND_PATH) . '/';
            $shopId = $this->createCsv->getCpssShopId($storeId);

            if (!file_exists($csvDir) || $storeCode != $sCode) {
                continue;
            }

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cpssFileTransfer.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('==========================CPSS File Transfer Start============================');
            $logger->info("==========================CPSS $storeCode File Transfer Start============================");

            if (!$isRealStore) {
                if ($handle = opendir($csvDir)) {
                    $this->logger->info("Check EC csv files for upload to CPSS Sftp Server.");
                    while (false !== ($file = readdir($handle))) {
                        if ($file != "." && $file != "..") {
                            $today = $this->createCsv->getCurrentDate();
                            $fileData = $this->getFileData($file, $storeCode);
                            //if (strpos($file, $today) == false && strpos($file, $shopId) !== false) {
                            $logger->info('FileName: ' . $file);
                            $logger->info('Date: ' . $today);
                            if (
                                /*strpos($file, $today) &&*/
                                strpos($file, $shopId) !== false &&
                                !empty($fileData) &&
                                isset($fileData['is_file_uploaded']) &&
                                !$fileData['is_file_uploaded']) {
                                //if (strpos($file, $shopId) !== false && !empty($fileData) && isset($fileData['is_file_uploaded']) && !$fileData['is_file_uploaded']) {
                                $filesToUpload[] = $file;
                            }
                        }
                    }
                    closedir($handle);
                } else {
                    $this->logger->critical(self::INVALID_DIR);
                }
            } else {
                if ($handle = opendir($csvDir)) {
                    $this->logger->info("Check Store csv files for upload to CPSS Sftp Server.");
                    while (false !== ($file = readdir($handle))) {
                        if ($file != "." && $file != "..") {
                            $today = $this->createCsv->getCurrentDate();
                            $fileData = $this->getFileData($file, $storeCode);
                            if (
                                /*strpos($file, $dateYesterday) &&*/
                                strpos($file, $shopId) == false &&
                                !empty($fileData) &&
                                isset($fileData['is_file_uploaded']) &&
                                !$fileData['is_file_uploaded']) {
                                //if (strpos($file, $shopId) !== false && !empty($fileData) && isset($fileData['is_file_uploaded']) && !$fileData['is_file_uploaded']) {
                                $filesToUpload[] = $file;
                            }
                        }
                    }

                    closedir($handle);
                } else {
                    $this->logger->critical(self::INVALID_DIR);
                }
            }

            $logger->info('FileUploadArray: ' . print_r($filesToUpload, true));
            $logger->info('==========================CPSS File Transfer End============================');
            $logger->info("==========================CPSS $storeCode File Transfer End============================");

            try {
                if (!empty($filesToUpload)) {
                    $this->logger->info("Transferring csv...", $filesToUpload);
                    if (!$isRealStore) {
                        foreach ($filesToUpload as $file) {
                            //change EC csv EOL before uploading to SFTP
                            $input = file_get_contents($csvDir . $file);
                            if ($input) {
                                $output = $this->createCsv->convertEOL($input);
                                file_put_contents($csvDir . $file, $output);
                            }
                        }
                    }

                    $connected = $this->sftpHelper->cpssConnect($storeId);

                    if ($connected) {
                        foreach ($filesToUpload as $file) {
                            $this->csvLogger->info("Put " . $file . " Host " . $this->sftpHelper->usedSftpServer);
                            $success = $this->sftpHelper->fileTransfer(
                                $cpssOutBound . $file,
                                $csvDir . $file
                            );

                            if ($success) {
                                $fileName = pathinfo($csvDir . $file, PATHINFO_FILENAME) . '.mk';
                                $isOK = $this->sftpHelper->fileTransfer(
                                    $cpssOutBound . $fileName,
                                    ""
                                );

                                if ($isOK) {
                                    //No problems with the CSV
                                    // Remove csv
                                    $this->updatePosUploadedFileStatus($file, $storeCode);
                                    $this->csvLogger->info("Delete " . $file);
                                    unlink($csvDir . $file);
                                }
                            }
                        }
                        $this->sftpHelper->logout();
                    } else {
                        $this->logger->error("Failed to transfer files to CPSS SFTP Server.", [$filesToUpload]);
                    }
                } else {
                    $this->logger->info("No files to upload.", $filesToUpload);
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * @param $fileName
     * @return void
     */
    private function updatePosUploadedFileStatus($fileName, $storeCode)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceConnection = $objectManager->create(\Magento\Framework\App\ResourceConnection::class);
        $connection  = $resourceConnection->getConnection();
        $tableName = $connection->getTableName('post_file_creation_time');
        $sql = "UPDATE $tableName SET `is_file_uploaded` = '1' WHERE $tableName.`file_name` = '$fileName' AND store_code='" . $storeCode . "'";
        $connection->query($sql);
    }

    /**
     * @param $fileName
     * @return mixed
     */
    private function getFileData($fileName, $storeCode)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceConnection = $objectManager->create(\Magento\Framework\App\ResourceConnection::class);
        $connection  = $resourceConnection->getConnection();
        $tableName = $connection->getTableName('post_file_creation_time');

        $query = 'SELECT * FROM ' . $tableName . ' where `file_name` like("' . $fileName . '%") AND store_code="' . $storeCode . '" ORDER BY `entity_id` DESC';
        return $resourceConnection->getConnection()->fetchRow($query);
    }

    public function getPath()
    {
        return $this->isRecover ? CreateCsv::CPSS_CSV_RECOVERY_DIR : CreateCsv::CPSS_CSV_DIR;
    }

    protected function getCsvDir()
    {
        $varDir = $this->dir->getPath(DirectoryList::VAR_DIR);
        return $varDir . '/' . $this->getPath();
    }

    protected function getDateYesterday()
    {
        return $this->createCsv->getCurrentDate(date('Y-m-d H:i:s', strtotime('-1 day')));
    }

    /**
     * getConfigValue
     *
     * @param  string $path
     * @param  null|int|string $scope
     * @return string
     */
    public function getConfigValue($path, $scope = null)
    {
        return $this->scopeConfig->getValue($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scope);
    }
}
