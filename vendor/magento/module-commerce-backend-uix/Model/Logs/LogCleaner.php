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

use Exception;
use Magento\CommerceBackendUix\Api\Data\LogInterface;
use Magento\CommerceBackendUix\Model\Config;
use Magento\CommerceBackendUix\Model\ResourceModel\Logs as ResourceModel;
use Magento\Framework\Intl\DateTimeFactory;
use Psr\Log\LoggerInterface;

/**
 * Log cleaner responsible for cleaning Admin UI SDK logs older than the configured retention period
 */
class LogCleaner
{
    /**
     * @param Config $config
     * @param DateTimeFactory $dateTimeFactory
     * @param ResourceModel $resourceModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Config $config,
        private DateTimeFactory $dateTimeFactory,
        private ResourceModel $resourceModel,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Clean logs older than the configured retention period
     *
     * @return void
     * @throws \Exception
     */
    public function clean(): void
    {
        try {
            $dateTime = $this->dateTimeFactory->create();
            $dateTime->sub(new \DateInterval(sprintf('P%uD', $this->config->getLogRetentionPeriod())));
            $this->resourceModel->deleteConditionally([
                LogInterface::FIELD_TIMESTAMP . ' <= ?' => $dateTime->getTimestamp()
            ]);
        } catch (Exception $e) {
            $this->logger->error(sprintf('Unable to delete Admin UI SDK logs: %s', $e->getMessage()));
        }
    }
}
