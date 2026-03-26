<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model\Logs;

use Magento\CommerceBackendUix\Api\Data\LogInterface;
use Magento\CommerceBackendUix\Api\LogRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Class for handling logs of Admin UI SDK
 */
class LoggerHandler
{
    private const ADMIN_UI_SDK_PREFIX = 'Admin UI SDK - ';

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Log error message
     *
     * @param string $message
     * @return void
     */
    public function error(string $message): void
    {
        $this->logger->error(self::ADMIN_UI_SDK_PREFIX . $message);
    }

    /**
     * Log warning message
     *
     * @param string $message
     * @return void
     */
    public function warning(string $message): void
    {
        $this->logger->warning(self::ADMIN_UI_SDK_PREFIX . $message);
    }

    /**
     * Log info message
     *
     * @param string $message
     * @return void
     */
    public function info(string $message): void
    {
        $this->logger->info(self::ADMIN_UI_SDK_PREFIX . $message);
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @return void
     */
    public function debug(string $message): void
    {
        $this->logger->debug(self::ADMIN_UI_SDK_PREFIX . $message);
    }
}
