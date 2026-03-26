<?php
//phpcs:ignoreFile
namespace Cpss\Crm\Cron;

use Magento\Framework\Filesystem\Io\Sftp;
use Cpss\Crm\Model\ResourceModel\RealStore as RealStoreResource;
use Cpss\Crm\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Cpss\Crm\Helper\SftpHelper;

class ShopInfo
{
    const SFTP_FILE_PATH = "sftp/cpss/in_path";

    const TABLE_COLUMNS = [
        'shop_id',
        'shop_status',
        'shop_name'
    ];

    protected $sftp;
    protected $realStore;
    protected $logger;
    protected $scopeConfig;
    protected $encryptor;
    protected $sftpHelper;

    public function __construct(
        Sftp $sftp,
        RealStoreResource $realStore,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        SftpHelper $sftphelper,
        private \OnitsukaTigerCpss\Crm\Helper\HelperData $helperData
    ) {
        $this->sftp = $sftp;
        $this->realStore = $realStore;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->sftpHelper = $sftphelper;
    }

    public function applyShopInfo($storeId)
    {
        if ($this->sftpHelper->cpssConnect($storeId)) {
            $this->logger->info("RealShop Cronjob: Connected");
            $this->process($storeId);
            $this->logger->info("RealShop Cronjob: Done. Logout.");
            $this->sftpHelper->logout();
        } else {
            $this->logger->error("RealShop Cronjob: Cannot connect to CPSS SFTP Server.");
        }
    }

    /**
     * Process saving of data
     * 1. Scan Files in specified directory
     * 2. Check file extension and size
     * 3. Validate headers
     * 4. Prepare data to be saved
     * 5. Save Data using insertOnDuplicate function
     *
     * @return void
     */
    public function process($storeId)
    {
        $storeIds = $this->helperData->getStoreIds();
        $storeCode = array_search ($storeId, $storeIds);

        try {
            $toUpdate = [];
            $csvToRemove = [];
            $sftpPath = $this->getConfigValue(self::SFTP_FILE_PATH) . '/';
            $isDir = $this->sftp->cd($sftpPath); // Set Directory
            if (!$isDir) {
                $this->logger->error(
                    "SFTP Directory does not exists. Please check directory.",
                    ["CPSS Inbound Path" => $sftpPath]
                );
                return;
            }
            $fileList = $this->sftp->rawls(); // Get File List inside current directory

            foreach ($fileList as $fileName => $info) {
                // Check File Extension
                $file = explode(".", $fileName);
                $ext = end($file);
                if ($ext != "csv" || (int) $info["size"] <= 0) {
                    if ($ext == "csv") {
                        $this->logger->warning("RealShop Cronjob: Empty Data. Removing... {$fileName}");
                        // Remove empty csv from sftp server
                        $this->sftp->rm($fileName);
                    }
                    continue;
                }

                // Read File Contents
                $content = $this->sftp->read($fileName);
                $data = $this->getData($content); // Convert content to array
                if (!empty($data)) {
                    try {
                        $this->logger->info("RealShop Cronjob: Get Data: {$fileName}");
                        for ($i = 0; $i < count($data); $i++) {
                            if ($i <= 0) { // Validate header
                                if (!$this->validateHeader($data)) {
                                    $this->logger->warning("RealShop Cronjob: Invalid Header: {$fileName}");
                                    continue;
                                }
                            } else {
                                $toUpdate[] = [
                                    'shop_id' => $data[$i][0],
                                    'shop_status' => $data[$i][1],
                                    'shop_name' => $data[$i][8],
                                    'country_code' => $storeCode
                                ];
                            }
                        }

                        $csvToRemove[] = $fileName;
                    } catch (\Exception $e) {
                        $this->logger->critical("RealShop Cronjob: Invalid File: {$fileName}");
                        $this->logger->critical($e->getMessage());
                    }
                }
            }
            
            if (!empty($toUpdate)) {
                try {
                    $this->logger->info("RealShop Cronjob: Insert Data:", $toUpdate);
                    $this->realStore->getConnection()->insertOnDuplicate('crm_real_stores', $toUpdate);
                } catch (\Exception $e) {
                    $this->logger->critical("RealShop Cronjob: Failed to insert data.");
                    $this->logger->critical($e->getMessage());
                }

                //Remove csv files if succesfully inserted to DB
                foreach ($csvToRemove as $k => $v) {
                    $removed = $this->sftp->rm($v);
                    if (!$removed) {
                        $this->logger->critical("Cannot remove file.", [$v]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Get Data (Converts content to array)
     *
     * @param  mixed $content
     * @return array
     */
    public function getData($content)
    {
        $row = str_getcsv($content, "\n");
        $length = count($row);

        $data = [];
        for ($i = 0; $i < $length; $i++) {
            $rowData = str_getcsv($row[$i], ",");
            $data[] = $rowData;
        }

        return $data;
    }

    /**
     * Get Config Value from Admin
     *
     * @param  string $path
     * @return string
     */
    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    /**
     * Validate header
     *
     * @param  array $header
     * @return bool
     */
    public function validateHeader($header)
    {
        try {
            return ($header[0][0] == "店舗ID" && $header[0][1] == "店舗ステータス" && $header[0][8] == "店舗名");
        } catch (\Exception $e) {
            return false;
        }
    }
}
