<?php

namespace OnitsukaTiger\Logger;

use Magento\Framework\Filesystem\DriverInterface;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/ot.log';

    /**
     * @param DriverInterface $filesystem
     * @param string $filePath
     * @param string $fileName
     * @param \Magento\Framework\App\State $appState
     * @throws \Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        \Magento\Framework\App\State $appState,
        $filePath = null,
        $fileName = null
    ) {
        switch ($appState->getMode()) {
            case \Magento\Framework\App\State::MODE_PRODUCTION:
                $this->loggerType = Logger::INFO;
                break;
            default:
                $this->loggerType = Logger::DEBUG;
                break;
        }
        parent::__construct($filesystem, $filePath, $fileName);
    }
}
