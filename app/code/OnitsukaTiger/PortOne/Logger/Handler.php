<?php
namespace OnitsukaTiger\PortOne\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    protected $fileName = '/var/log/portone.log';
    protected $loggerType = Logger::DEBUG;
}
