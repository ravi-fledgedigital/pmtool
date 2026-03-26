<?php

namespace Seoulwebdesign\KakaoSync\Helper;

use Magento\Framework\Filesystem\DirectoryList;
use Monolog\Handler\StreamHandler;

class Logger
{
    public const LOG_PREFIX = 'kakaosync-';
    public const LOG_PATH = 'kakaosync';
    public const LOG_NAME = 'KakaoSyncLog';
    /**
     * @var string
     */
    protected $logPath;
    /**
     * @var array
     */
    protected $logObjectCache;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var mixed|string
     */
    protected $logName;

    /**
     * @param DirectoryList $directoryList
     * @param string $logPath
     * @param string $logName
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        DirectoryList $directoryList,
        $logPath = '',
        $logName = ''
    ) {
        $this->directoryList = $directoryList;
        $this->logPath = $logPath ?: $this->directoryList->getPath('var').'/log/'.self::LOG_PATH.'/';
        $this->logName = $logName ?: self::LOG_NAME;
        $this->logObjectCache = [];
    }

    /**
     * Log debug info
     *
     * @param mixed $message
     * @param string $fileName
     * @param array $context
     */
    public function logDebug($message, $fileName, $context = [])
    {
        try {
            $message = is_string($message) ? $message : json_encode($message);
            $logfile = $this->logPath. self::LOG_PREFIX . $fileName . '-debug.log';
            $handler = new StreamHandler($logfile, \Monolog\Logger::DEBUG);
            $logger = new \Monolog\Logger($this->logName);
            $logger->pushHandler($handler)->debug($message, $context);
        } catch (\Throwable $t) {
            return;
        }
    }

    /**
     * Log error info
     *
     * @param mixed $message
     * @param string $fileName
     * @param array $context
     */
    public function logError($message, $fileName, $context = [])
    {
        try {
            $message = is_string($message) ? $message : json_encode($message);
            $logfile = $this->logPath. self::LOG_PREFIX. $fileName.'-error.log';
            $handler = new StreamHandler($logfile, \Monolog\Logger::ERROR);
            $logger = new \Monolog\Logger($this->logName);
            $logger->pushHandler($handler)->error($message, $context);
        } catch (\Throwable $t) {
            return;
        }
    }

    /**
     * Log info level
     *
     * @param mixed $message
     * @param string $fileName
     * @param array $context
     */
    public function logInfo($message, $fileName, $context = [])
    {
        try {
            $message = is_string($message) ? $message : json_encode($message);
            $logfile = $this->logPath. self::LOG_PREFIX. $fileName.'-info.log';
            $handler = new StreamHandler($logfile, \Monolog\Logger::INFO);
            $logger = new \Monolog\Logger($this->logName);
            $logger->pushHandler($handler)->info($message, $context);
        } catch (\Throwable $t) {
            return;
        }
    }
}
