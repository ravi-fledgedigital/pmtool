<?php

namespace Seoulwebdesign\Toast\Helper;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/toast/debug.log';
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::DEBUG;
}
