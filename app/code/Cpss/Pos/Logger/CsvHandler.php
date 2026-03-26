<?php

namespace Cpss\Pos\Logger;

use Monolog\Logger;

class CsvHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/cpssCsvTransfer.log';
}
