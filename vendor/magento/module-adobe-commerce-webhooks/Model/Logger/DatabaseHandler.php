<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\Logger;

use Exception;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Request\RequestIdInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookLogInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\System\Config;
use Magento\AdobeCommerceWebhooks\Model\ResourceModel\WebhookLog;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookLogFactory;
use Magento\AdobeCommerceWebhooks\Model\WebhookLogResource;
use Monolog\Handler\HandlerInterface;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;

/**
 * Database handler for storing webhook logs in the database.
 */
class DatabaseHandler implements HandlerInterface
{
    /**
     * @param Config $config
     * @param WebhookLogFactory $webhookLogFactory
     * @param RequestIdInterface $requestId
     * @param LoggerInterface $logger
     * @param WebhookLogResource $webhookLogResource
     */
    public function __construct(
        private readonly Config $config,
        private readonly WebhookLogFactory $webhookLogFactory,
        private readonly RequestIdInterface $requestId,
        private readonly LoggerInterface $logger,
        private readonly WebhookLogResource $webhookLogResource
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isHandling(array|LogRecord $record): bool
    {
        return $this->config->isDbLogEnabled() && $record['level'] >= $this->config->getDbLogLevel();
    }

    /**
     * Stores the webhook log record in the database.
     *
     * @param array|LogRecord $record
     * @return bool
     */
    public function handle(array|LogRecord $record): bool
    {
        if (!$this->isHandling($record) || !isset($record['context']['hook'])) {
            return false;
        }
        /**
         * @var Hook $hook
         */
        $hook = $record['context']['hook'];

        if (!$this->config->isFullLogMessageEnabled() && !empty($record['context']['ui_message'])) {
            $message = $record['context']['ui_message'];
        } else {
            $message = $record['message'];
        }

        $webhook = $hook->getBatch()->getWebhook();

        $webhookLogRecord = $this->webhookLogFactory->create([
            'data' => [
                WebhookLogInterface::FIELD_LEVEL => $record['level'],
                WebhookLogInterface::FIELD_WEBHOOK_METHOD => $webhook->getName(),
                WebhookLogInterface::FIELD_WEBHOOK_TYPE => $webhook->getType(),
                WebhookLogInterface::FIELD_BATCH_NAME => $hook->getBatch()->getName(),
                WebhookLogInterface::FIELD_HOOK_NAME => $hook->getName(),
                WebhookLogInterface::FIELD_REQUEST_ID => $this->requestId->get(),
                WebhookLogInterface::FIELD_MESSAGE => $message,
            ]
        ]);
        $webhookLogRecord->setHasDataChanges(true);
        $connection = $this->webhookLogResource->getConnection();

        try {
            $connection->beginTransaction();
            $connection->insert(WebhookLog::TABLE_NAME, $webhookLogRecord->getData());
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            $this->logger->error(sprintf('Unable to save webhook log into database: %s', $e->getMessage()));
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
