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

namespace Magento\AdobeCommerceWebhooks\Model\ResourceModel\WebhookLog;

use Magento\AdobeCommerceWebhooks\Model\ResourceModel\WebhookLog as WebhookLogResourceModel;
use Magento\AdobeCommerceWebhooks\Model\WebhookLog;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Resource collection for the WebhookLog model
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(WebhookLog::class, WebhookLogResourceModel::class);
    }
}
