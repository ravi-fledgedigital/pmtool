<?php

namespace OnitsukaTiger\DiscountLimit\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * @package    OnitsukaTiger_DiscountLimit
 */
class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/onitsukatiger_discountlimit.log';

    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
}
