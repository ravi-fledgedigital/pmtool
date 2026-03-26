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
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookLogInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\System\Config;
use Magento\AdobeCommerceWebhooks\Model\ResourceModel\WebhookLog as WebhookLogResourceModel;
use Magento\Framework\Intl\DateTimeFactory;
use Psr\Log\LoggerInterface;

/**
 * Cleans up webhook logs that are no longer needed.
 */
class WebhookLogCleaner
{
    /**
     * @param WebhookLogResourceModel $webhookLogResourceModel
     * @param LoggerInterface $logger
     * @param DateTimeFactory $dateTimeFactory
     * @param Config $config
     */
    public function __construct(
        private WebhookLogResourceModel $webhookLogResourceModel,
        private LoggerInterface $logger,
        private DateTimeFactory $dateTimeFactory,
        private Config  $config
    ) {
    }

    /**
     * Deletes webhook log records in the webhooks_log table according to retention period value.
     *
     * @return void
     */
    public function clean(): void
    {
        try {
            $dateTime = $this->dateTimeFactory->create();
            $dateTime->sub(new \DateInterval(sprintf('P%uD', $this->config->getDbLogRetentionPeriod())));
            $this->webhookLogResourceModel->deleteConditionally([
                WebhookLogInterface::FIELD_CREATED_AT . ' <= ?' => $dateTime->format('Y-m-d h:i:s')
            ]);
        } catch (Exception $e) {
            $this->logger->error(sprintf('Unable to delete webhook logs: %s', $e->getMessage()));
        }
    }
}
