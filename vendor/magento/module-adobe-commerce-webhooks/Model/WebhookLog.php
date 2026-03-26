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

namespace Magento\AdobeCommerceWebhooks\Model;

use Magento\AdobeCommerceWebhooks\Api\Data\WebhookLogInterface;
use Magento\AdobeCommerceWebhooks\Model\ResourceModel\WebhookLog as WebhookLogResourceModel;
use Magento\Framework\Model\AbstractModel;

/**
 * Webhook log data model
 */
class WebhookLog extends AbstractModel implements WebhookLogInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(WebhookLogResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getId(): ?string
    {
        return parent::getData(self::FIELD_ID);
    }

    /**
     * @inheritDoc
     */
    public function getWebhookMethod(): string
    {
        return $this->getData(self::FIELD_WEBHOOK_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function getWebhookType(): string
    {
        return $this->getData(self::FIELD_WEBHOOK_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function getBatchName(): string
    {
        return $this->getData(self::FIELD_BATCH_NAME);
    }

    /**
     * @inheritDoc
     */
    public function getHookName(): string
    {
        return $this->getData(self::FIELD_HOOK_NAME);
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return $this->getData(self::FIELD_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::FIELD_CREATED_AT);
    }
}
