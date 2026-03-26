<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * System / Cache Management / Cache type "Webhooks Response Cache"
 */
class WebhookResponse extends TagScope
{
    public const TYPE_IDENTIFIER = 'webhooks_response';
    public const CACHE_TAG = 'WEBHOOKS_RESPONSE';

    /**
     * @param FrontendPool $frontendPool
     */
    public function __construct(FrontendPool $frontendPool)
    {
        parent::__construct(
            $frontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
