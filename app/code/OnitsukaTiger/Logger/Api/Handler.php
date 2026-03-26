<?php

namespace OnitsukaTiger\Logger\Api;

use Magento\Framework\Filesystem\DriverInterface;

class Handler extends \OnitsukaTiger\Logger\Handler
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/ot_api.log';
}
