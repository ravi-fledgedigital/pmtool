<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Gdx\CustomLogging\Logger;

use DateTimeZone;
use Magento\Store\Model\ScopeInterface;
use Monolog\DateTimeImmutable;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;

/**
 * Class Monolog
 *
 * @package Gdx\CustomLogging\Logger
 */
class Monolog extends \Magento\Framework\Logger\Monolog
{
    const XML_LOGGER_FORMATTER_PATH = "logger/custom_logging/logger_formatter";

    /**
     * Adds a log record
     *
     * @param int $level The logging level
     * @param string $message The log message
     * @param array $context The log context
     * @param DateTimeImmutable|null $datetime Optional log date to log into the past or future
     * @return bool Whether the record has been processed
     * @throws \Zend_Log_Exception
     */
    public function addRecord(int $level, string $message, array $context = [], DateTimeImmutable $datetime = null): bool
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);

            $loggerFormatter = $scopeConfig->getValue(self::XML_LOGGER_FORMATTER_PATH, ScopeInterface::SCOPE_STORE);
            if ($loggerFormatter && $loggerFormatter == \Gdx\CustomLogging\Model\Source\Logger\Formatter::JSON_FORMATTER) {
                if (!$this->handlers) {
                    $this->pushHandler(new StreamHandler('php://stderr', static::DEBUG));
                }
                if (count($this->handlers)) {
                    foreach ($this->handlers as &$handler) {
                        if (!$handler->getFormatter() instanceof JsonFormatter) {
                            $handler->setFormatter(new JsonFormatter(false, true));
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            parent::addRecord(static::ERROR, 'Something went wrong while handling the data with Gdx Custom Logging', $context);
        }
        return parent::addRecord($level, $message, $context);
    }
}
