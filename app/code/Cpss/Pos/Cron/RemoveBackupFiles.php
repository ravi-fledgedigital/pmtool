<?php

namespace Cpss\Pos\Cron;

use Cpss\Pos\Cron\UpdatePosData;
use \DirectoryIterator;

class RemoveBackupFiles
{
    /**
     * @var \Cpss\Pos\Logger\CsvLogger
     */
    private $csvLogger;

    public function __construct(
        \Cpss\Pos\Logger\CsvLogger $csvLogger
    ) {
        $this->csvLogger = $csvLogger;
    }

    public function execute()
    {
        $dir = UpdatePosData::POS_CSV_LOCAL_DIR;
        $now = time();
        $week = (60 * 60 * 24 * 7); // week in seconds

        if (file_exists($dir)) {
            foreach (new DirectoryIterator($dir) as $file) {
                $cTime = $file->getCTime();
                $fileTime = $now - $cTime;
                if ($file->isFile() && $cTime && $fileTime > $week) {
                    $this->csvLogger->info("Removing Backup File ", [$file]);
                    unlink($file->getRealPath());
                }
            }
        }
    }
}
