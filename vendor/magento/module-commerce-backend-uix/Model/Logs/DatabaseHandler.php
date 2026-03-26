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
use Magento\CommerceBackendUix\Model\Config;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Monolog\Handler\HandlerInterface;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;

/**
 * Class for handling logs in database of Admin UI SDK
 */
class DatabaseHandler implements HandlerInterface
{
    /**
     * @param LogRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param Config $config
     * @param LogFactory $logFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly LogRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly Config $config,
        private readonly LogFactory $logFactory,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isHandling(array|LogRecord $record): bool
    {
        return $this->config->isDatabaseLoggingEnabled() && $record['level'] >= $this->config->getLogLevel();
    }

    /**
     * Stores Admin UI SDK log into database
     *
     * @param LogRecord $record
     * @return bool
     */
    public function handle(array|LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $logRecord = $this->logFactory->create([
            'data' => [
                LogInterface::FIELD_MESSAGE => $record['message'],
                LogInterface::FIELD_LEVEL => $record['level']
            ]
        ]);
        $logRecord->setTimestamp((string) $this->dateTime->timestamp());

        try {
            $this->repository->save($logRecord);
        } catch (AlreadyExistsException $e) {
            $this->logger->error(sprintf('Unable to save Admin UI SDK log into database: %s', $e->getMessage()));
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        //phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
        return;
    }
}
