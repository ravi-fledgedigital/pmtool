<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Seoulwebdesign\Kakaopay\Gateway\Http\Client;

use Seoulwebdesign\Base\Gateway\Http\Client\AbstractTransaction;
use Seoulwebdesign\Kakaopay\Helper\ConfigHelper;
use Seoulwebdesign\Kakaopay\Helper\Constant;
use Seoulwebdesign\Kakaopay\Logger\Logger;

/**
 * Class TransactionRefund
 */
class TransactionCapture extends AbstractTransaction
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;
    /**
     * @var Logger
     */
    protected $customLogger;


    /**
     * Constructor
     *
     * @param Logger $customLogger
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Logger $customLogger,
        ConfigHelper $configHelper
    ) {
        $this->customLogger = $customLogger;
        $this->configHelper = $configHelper;
        parent::__construct($customLogger);
    }

    /**
     * @param array $data
     * @return array|mixed
     */
    protected function process(array $data)
    {
        $response = $this->configHelper->sendCurl(Constant::KAKAOPAY_PAYMENT_APPROVE, $data, 'POST');
        $this->myLogger("response//" . print_r($response, true));
        return $response;
    }

    /**
     * @param $mess
     */
    public function myLogger($mess)
    {
        if ($this->configHelper->getCanDebug()) {
            $this->customLogger->debug($mess);
        }
    }
}
