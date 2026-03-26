<?php

namespace Cpss\Pos\Cron;

use Cpss\Pos\Helper\CreateCsv;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Cpss\Pos\Logger\Logger;

/**
 * Create CSV files for Tomorrow's batch
 */
class CreateCsvFiles
{
    protected $dirList;
    protected $timezone;
    protected $scopeConfig;
    protected $createCsv;
    protected $filesystem;
    protected $logger;

    public function __construct(
        DirectoryList $dirList,
        TimezoneInterface $timezone,
        CreateCsv $createCsv,
        Filesystem $filesystem,
        Logger $logger
    ) {
        $this->dirList = $dirList;
        $this->timezone = $timezone;
        $this->createCsv = $createCsv;
        $this->filesystemRead = $filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $this->filesystemWrite = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->logger = $logger;
    }

    public function execute()
    {
        $varDir = $this->dirList->getPath('var');
        $csvDir = $varDir . '/' . CreateCsv::CPSS_CSV_DIR;
        $shopId = $this->createCsv->getCpssShopId();
        $date = $this->timezone->date(date("Y-m-d", strtotime("+1 day")));
        $dateTomorrow = $date->format('Ymd');
        $initialFileName = '_' . $dateTomorrow . '_' . $shopId . '_' . str_pad(0, 3, 0, STR_PAD_LEFT) . '.csv';
        $csvFiles = [
            'HEADER' => 'POS_HEADER' . $initialFileName,
            'DETAIL' => 'POS_DETAIL' . $initialFileName,
            'PRODUCT' => 'POS_PRODUCT' . $initialFileName
        ];

        if (!file_exists($this->filesystemRead->getAbsolutePath($csvDir))) {
            $this->createCsv->createFileDirectory($csvDir);
        }

        foreach ($csvFiles as $key => $file) {
            if (!file_exists($this->filesystemRead->getAbsolutePath($file))) {
                if ($key == "PRODUCT") {
                    touch($csvDir . $file);
                    continue;
                }
                $this->createCsv->createCsv($file, [], $key);
            }
        }
    }
}
